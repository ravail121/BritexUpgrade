<?php

namespace App\Http\Controllers\Api\V1\BulkOrder;

use Validator;
use Carbon\Carbon;
use App\Model\Sim;
use App\Model\Plan;
use App\Model\Addon;
use App\Helpers\Log;
use App\Model\Order;
use App\Model\Device;
use App\Model\Company;
use App\Model\Customer;
use App\Model\OrderGroup;
use App\Model\PlanToAddon;
use App\Model\Subscription;
use Illuminate\Http\Request;
use App\Model\CustomerProduct;
use App\Model\SubscriptionLog;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use App\Model\CustomerStandaloneSim;
use App\Model\CustomerStandaloneDevice;
use App\Http\Controllers\BaseController;
use App\libs\Constants\ConstantInterface;
use App\Http\Controllers\Api\V1\Traits\BulkOrderTrait;

/**
 * Class CheckoutController
 *
 * @package App\Http\Controllers\Api\V1
 */
class CheckoutController extends BaseController implements ConstantInterface
{

	use BulkOrderTrait;
	/**
	 * @param Request $request
	 *
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function simsForCatalogue(Request $request)
	{
		try {
			$requestCompany = $request->get('company');

			$validator = Validator::make( $request->all(), [
				'customer_id'           => [
					'numeric',
					'required',
					Rule::exists('customer', 'id')->where(function ($query) use ($requestCompany) {
						return $query->where('company_id', $requestCompany->id);
					})
				]
			]);

			if ($validator->fails()) {
				$errors = $validator->errors();
				return $this->respondError($errors->messages(), 422);
			}

			$perPage = $request->has('per_page') ? (int) $request->get('per_page') : 50;
			$customerProducts = CustomerProduct::where('customer_id', $request->get('customer_id'))
			                                   ->where('product_type', CustomerProduct::PRODUCT_TYPES['sim'])
			                                   ->pluck('product_id')->toArray();

			if($customerProducts) {
				$sims = Sim::whereIn( 'id', $customerProducts )->paginate( $perPage );

				return $this->respond( $sims );
			} else {
				return $this->respond( [] );
			}

		} catch(\Exception $e) {
			Log::info($e->getMessage(), 'Error in simsForCatalogue');
			return $this->respondError($e->getMessage());
		}
	}

	/**
	 * @param Request $request
	 *
	 * @return \Illuminate\Http\JsonResponse|string
	 */
	public function createOrder(Request $request)
	{
		try {
			$planActivation = $request->get('plan_activation') ?: false;
			$numberChange = $request->get('number_change') ?: false;
			$validation = $this->validationRequestForBulkOrder($request, $planActivation, $numberChange);
			if($validation !== 'valid') {
				return $validation;
			}

			$data = $request->all();

			$orderTransaction = DB::transaction(function () use ($request, $data, $planActivation, $numberChange) {

				$customer = Customer::find($request->get('customer_id'));

				/**
				 * Create new row in order table if the order is not for plan activation
				 */
				$order = Order::create( [
					'hash'              => sha1( time() . rand() ),
					'company_id'        => $request->get( 'company' )->id,
					'customer_id'       => $data[ 'customer_id' ],
					'shipping_fname'    => $request->get('shipping_fname') ?: $customer->billing_fname,
					'shipping_lname'    => $request->get('shipping_lname') ?: $customer->billing_lname,
					'shipping_address1' => $request->get('shipping_address1') ?: $customer->billing_address1,
					'shipping_address2' => $request->get('shipping_address2') ?: $customer->billing_address2,
					'shipping_city'     => $request->get('shipping_city') ?: $customer->billing_city,
					'shipping_state_id' => $request->get('shipping_state_id') ?: $customer->billing_state_id,
					'shipping_zip'      => $request->get('shipping_zip') ?: $customer->billing_zip
				] );

				if($request->has('billing_state_id')) {
					$customerData['billing_state_id'] = $request->get('billing_state_id');
					$customerData['billing_fname'] = $request->get('billing_fname');
					$customerData['billing_lname'] = $request->get('billing_lname');
					$customerData['billing_address1'] = $request->get('billing_address1');
					$customerData['billing_address2'] = $request->get('billing_address2');
					$customerData['billing_city'] = $request->get('billing_city');
					$customerData['billing_zip'] = $request->get('billing_zip');
					$customer->update($customerData);
				}

				$orderItems = $request->get( 'orders' );

				foreach ( $orderItems as $orderItem ) {
					$order_group = null;
					$paidMonthlyInvoice = isset( $orderItem[ 'paid_monthly_invoice' ] ) ? $orderItem[ 'paid_monthly_invoice' ] : null;
					$order_group = OrderGroup::create( [
						'order_id' => $order->id
					] );

					/**
					 * @internal Add requested_zip for number change
					 */
					if($numberChange){
						$order_group->update([
							'requested_zip' => $request->get('zip_code')
						]);
					}

					if($order_group) {
						$this->insertOrderGroupForBulkOrder( $orderItem, $order, $order_group );
						if ( isset( $paidMonthlyInvoice ) && $paidMonthlyInvoice == "1" && isset( $orderItem[ 'plan_id' ] ) ) {
							$monthly_order_group = OrderGroup::create( [
								'order_id' => $order->id
							] );
							$this->insertOrderGroupForBulkOrder( $orderItem, $order, $monthly_order_group, 1 );
						}
					}
				}
				return $order;
			});
			$successResponse = [
				'status'  => 'success',
				'data'    => [
					'order_hash'    => $orderTransaction ? $orderTransaction->hash : null
				],
				'message' => 'Order created successfully'
			];
			return $this->respond($successResponse);

		} catch(\Exception $e) {
			Log::info($e->getMessage() . ' on line number: '.$e->getLine() . ' Create Order for Bulk Order');
			$response = [
				'status' => 'error',
				'data'   => $e->getMessage()
			];
			return $this->respond( $response, 400 );
		}
	}

	/**
	 * Preview Order for Bulk Order
	 * @param Request $request
	 *
	 * @return \Illuminate\Http\JsonResponse|string
	 */
	public function orderSummaryForBulkOrder(Request $request)
	{
		try {
			$orderSubscription = $request->get('plan_activation') ?: false;
			$numberChange = $request->get('number_change') ?: false;
			$validation = $this->validationRequestForBulkOrder($request, $orderSubscription, $numberChange);
			if($validation !== 'valid') {
				return $validation;
			}
			$output = [];
			$orderItems = $request->get( 'orders' );
			$customer = Customer::find($request->get('customer_id'));

			$totalWithoutSurcharge = $this->totalPriceForPreview($request, $orderItems, false);

			$output['totalPrice'] =  $this->totalPriceForPreview($request, $orderItems);
			$output['subtotalPrice'] = $this->subTotalPriceForPreview($request, $orderItems);
			$output['monthlyCharge'] = $this->calMonthlyChargeForPreview($orderItems);
			$output['taxes'] = $this->calTaxesForPreview($request, $orderItems);
			$output['regulatory'] = $this->calRegulatoryForPreview($request, $orderItems);
			$output['activationFees'] = $this->getPlanActivationPricesForPreview($orderItems);
			$output['surcharge'] = $this->getSurchargeAmountForPreview($totalWithoutSurcharge, $customer);
			$costBreakDown = (object) [
				'devices'   => $this->getCostBreakDownPreviewForDevices($request, $orderItems),
				'plans'     => $this->getCostBreakDownPreviewForPlans($request, $orderItems),
				'sims'      => $this->getCostBreakDownPreviewForSims($request, $orderItems),
				'addons'    => $this->getCostBreakDownPreviewForAddons($request, $orderItems, $customer),
			];

			$output['summary'] = $costBreakDown;
			$successResponse = [
				'status'    => 'success',
				'data'      => $output
			];
			return $this->respond($successResponse);

		} catch(\Exception $e) {
			Log::info( $e->getMessage(), 'Order Summary for Bulk Order' );
			$response = [
				'status' => 'error',
				'data'   => $e->getMessage()
			];
			return $this->respond( $response, 503 );
		}
	}

	/**
	 * @param $request
	 * @param $orderSubscription
	 * @param $numberChange
	 *
	 * @return \Illuminate\Http\JsonResponse|string
	 */
	private function validationRequestForBulkOrder($request, $orderSubscription, $numberChange=false)
	{
		$requestCompany = $request->get('company');
		if ( !$requestCompany->enable_bulk_order ) {
			$notAllowedResponse = [
				'status' => 'error',
				'data'   => 'Bulk Order not enabled for the requesting company'
			];
			return $this->respond($notAllowedResponse, 503);
		}
		$baseValidation = [
			'customer_id'                   => [
				'required',
				'numeric',
				Rule::exists('customer', 'id')->where(function ($query) use ($requestCompany) {
					return $query->where('company_id', $requestCompany->id);
				})
			],
			'orders'                        => 'required',
			'orders.*.device_id'            => [
				'numeric',
				Rule::exists('device', 'id')->where(function ($query) use ($requestCompany) {
					return $query->where('company_id', $requestCompany->id);
				})
			],
			'orders.*.subscription_id'      => [
				'numeric',
				Rule::exists('subscription', 'id')->where(function ($query) use ($requestCompany) {
					return $query->where('company_id', $requestCompany->id);
				})
			],
			'orders.*.addon_id.*'      => [
				'numeric',
				Rule::exists('addon', 'id')->where(function ($query) use ($requestCompany) {
					return $query->where('company_id', $requestCompany->id);
				})
			],
			'orders.*.subscription_status'  => 'string',
			'shipping_fname'                => 'string',
			'shipping_lname'                => 'string',
			'shipping_address1'             => 'required|string',
			'shipping_address2'             => 'nullable|string',
			'shipping_city'                 => 'required|string',
			'shipping_state_id'             => 'required|string|max:2',
			'shipping_zip'                  => 'required|string',
			'billing_state_id'              => 'nullable|string|max:2',
			'billing_fname'                 => 'required_with:billing_state_id|string',
			'billing_lname'                 => 'required_with:billing_state_id|string',
			'billing_address1'              => 'required_with:billing_state_id|string',
			'billing_address2'              => 'nullable|string',
			'billing_city'                  => 'required_with:billing_state_id|string',
			'billing_zip'		            => 'required_with:billing_state_id|string',
		];
		if($orderSubscription) {
			$baseValidation['orders.*.plan_id']              = [
				'required',
				'numeric',
				Rule::exists('plan', 'id')->where(function ($query) use ($requestCompany) {
					return $query->where('company_id', $requestCompany->id);
				})
			];
			$baseValidation['orders.*.sim_id']               =  [
				'required',
				'numeric',
				Rule::exists('sim', 'id')->where(function ($query) use ($requestCompany) {
					return $query->where('company_id', $requestCompany->id);
				})
			];
		} elseif($numberChange){
			$baseValidation['orders.*.addon_id.*']              = [
				'numeric',
				'required',
				Rule::exists('addon', 'id')->where(function ($query) use ($requestCompany) {
					return $query->where('company_id', $requestCompany->id);
				})
			];
			$baseValidation['orders.*.subscription_id']              = [
				'required_without:orders.*.phone_number',
				'numeric',
				Rule::exists('subscription', 'id')->where(function ($query) use ($requestCompany, $request) {
					return $query->where('company_id', $requestCompany->id)
						->where('customer_id', $request->customer_id);
				})
			];
			$baseValidation['orders.*.phone_number']              = [
				'required_without:orders.*.subscription_id',
				'numeric',
				Rule::exists('subscription', 'phone_number')->where(function ($query) use ($requestCompany, $request) {
					return $query->where('company_id', $requestCompany->id)
					             ->where('customer_id', $request->customer_id);
				})
			];
			$baseValidation['zip_code']              = [
				'min:5',
				'max:5',
			];

		} else {
			$baseValidation['orders.*.plan_id']              = [
				'numeric',
				Rule::exists('plan', 'id')->where(function ($query) use ($requestCompany) {
					return $query->where('company_id', $requestCompany->id);
				})
			];
			$baseValidation['orders.*.sim_id']               =  [
				'numeric',
				Rule::exists('sim', 'id')->where(function ($query) use ($requestCompany) {
					return $query->where('company_id', $requestCompany->id);
				})
			];
		}
		$validation = Validator::make(
			$request->all(),
			$baseValidation
		);

		if ( $validation->fails() ) {
			$errors = $validation->errors();
			$validationErrorResponse = [
				'status' => 'error',
				'data'   => $errors->messages()
			];
			return $this->respond($validationErrorResponse, 422);
		}
		return 'valid';
	}

	/**
	 * @param Request $request
	 *
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function listOrderSims(Request $request)
	{
		try {
			$requestCompany = $request->get('company');
			$perPage = $request->has('per_page') ? (int) $request->get('per_page') : 10;

			$customer = Customer::where('company_id', $requestCompany->id)->where('id', $request->get('customer_id'))->first();

			$simId = $request->get('sim_id');

			if($simId) {
				$orderSims = CustomerStandaloneSim::where( [
					[ 'sim_id', $simId ],
					[ 'customer_id', $customer->id ],
					[ 'status', CustomerStandaloneSim::STATUS['complete'] ],
					[ 'subscription_id', null ]
				] )->whereHas( 'sim', function ( $query ) use ( $requestCompany, $simId ) {
					$query->where(
						[
							[ 'company_id', $requestCompany->id ]
						]
					);
				} )->with( 'sim' )->paginate($perPage);
			} else {
				$orderSims = CustomerStandaloneSim::where( [
					[ 'customer_id', $customer->id ],
					[ 'subscription_id', null ],
					[ 'status', CustomerStandaloneSim::STATUS['complete'] ],
				] )->whereHas( 'sim', function ( $query ) use ( $requestCompany ) {
					$query->where(
						[
							[ 'company_id', $requestCompany->id ]
						]
					);
				} )->with( 'sim' )->paginate($perPage);
			}

			return $this->respond($orderSims);

		} catch(\Exception $e) {
			Log::info($e->getMessage(), 'Error in list order sims');
			return $this->respondError($e->getMessage());
		}
	}

	/**
	 * List order plans
	 * @param Request $request
	 *
	 * @return \Illuminate\Http\JsonResponse|void
	 */
	public function listOrderPlans(Request $request)
	{
		try {
			$requestCompany = $request->get('company');
			$validator = Validator::make( $request->all(), [
				'sim_id'               =>  [
					'numeric',
					'required',
					Rule::exists('sim', 'id')->where(function ($query) use ($requestCompany) {
						return $query->where([
							[ 'company_id', $requestCompany->id ]
						]);
					}),
					Rule::exists('customer_products', 'product_id')->where(function ($query) {
						return $query->where('product_type', CustomerProduct::PRODUCT_TYPES['sim']);
					})
				]
			]);

			if ($validator->fails()) {
				$errors = $validator->errors();
				return $this->respondError($errors->messages(), 422);
			}

			$sim = Sim::where('id', $request->get('sim_id'))->first();

			$customerProducts = CustomerProduct::where('customer_id', $request->get('customer_id'))
			                                   ->where('product_type', CustomerProduct::PRODUCT_TYPES['plan'])
			                                   ->pluck('product_id')->toArray();

			if($customerProducts) {
				$orderPlans = Plan::where( [
					[ 'company_id', $requestCompany->id ],
					[ 'carrier_id', $sim->carrier_id ]
				] )->whereIn( 'id', $customerProducts )->get();

				return $this->respond( $orderPlans );
			} else {
				return $this->respond( [] );
			}

		} catch(\Exception $e) {
			Log::info($e->getMessage(), 'Error in list order plans');
			return $this->respondError($e->getMessage());
		}
	}

	/**
	 * Order subscription from CSV file
	 * @param Request $request
	 *
	 * @return \Illuminate\Http\JsonResponse|void
	 */
	public function csvOrderSubscriptions(Request $request)
	{
		try {
			$error = [];
			$requestCompany = $request->get('company');

			$validator = Validator::make( $request->all(), [
				'csv_file'              =>  'required',
				'sim_id'               =>  [
					'numeric',
					'required',
					Rule::exists('sim', 'id')->where(function ($query) use ($requestCompany) {
						return $query->where('company_id', $requestCompany->id);
					}),
					Rule::exists('customer_products', 'product_id')->where(function ($query) {
						return $query->where('product_type', CustomerProduct::PRODUCT_TYPES['sim']);
					})
				],
				'plan_id'               =>  [
					'numeric',
					'required',
					Rule::exists('plan', 'id')->where(function ($query) use ($requestCompany) {
						return $query->where('company_id', $requestCompany->id);
					}),
					Rule::exists('customer_products', 'product_id')->where(function ($query) {
						return $query->where('product_type', CustomerProduct::PRODUCT_TYPES['plan']);
					})
				],
				'customer_id'           => [
					'numeric',
					'required',
					Rule::exists('customer', 'id')->where(function ($query) use ($requestCompany) {
						return $query->where('company_id', $requestCompany->id);
					})
				]
			]);

			if ($validator->fails()) {
				$errors = $validator->errors();
				return $this->respondError($errors->messages(), 422);
			}
			$customerId = $request->get('customer_id');

			$customer = Customer::find($customerId);

			$planActivation = $request->post('plan_activation') ?: true;

			$csvFile = $request->post('csv_file');

			$simId = $request->get('sim_id');

			$planId = $request->get('plan_id');

			$plan = Plan::find($planId);

			/**
			 * Validate if the input file is CSV file
			 */
			if (preg_match('/^data:text\/(\w+);base64,/', $csvFile) || preg_match('/^data:application\/(\w+);base64,/', $csvFile) || preg_match('/^data:@file\/(\w+);base64,/', $csvFile)) {
				$csvFile = substr($csvFile, strpos($csvFile, ',') + 1);
				$csvFile = base64_decode($csvFile);
			} else {
				return $this->respondError('CSV file not uploaded', 422);
			}

			if ($csvFile) {
				$csvAsArray = str_getcsv( $csvFile, "\n" );
				$headerRows = array_shift( $csvAsArray );
				$headerRowsArray = explode( ',', $headerRows );
				$csvAsArray = array_map( function ( $row ) use ( $headerRowsArray ) {
					return array_combine( $headerRowsArray, str_getcsv( $row ) );
				}, $csvAsArray );

				$sim = Sim::find($simId);
				foreach ( $csvAsArray as $rowIndex => $row ) {
					$rowNumber = $rowIndex + 1;
					if($plan->carrier->slug === 'ultra' && $row['zip_code'] && !$this->isZipCodeValid($row['zip_code'], $requestCompany)) {
						$error[] = "Zip code {$row['zip_code']} is not valid for row $rowNumber";
					}
					$row['sim_num'] = str_replace("'", '', $row['sim_num']);
					if(!$this->simNumberExistsForCustomer($row['sim_num'], $customer)) {
						$error[] = "Phone number {$row['sim_num']} is not valid for row $rowNumber";
					}

					$subscriptionOrders[] = $row;
				}

				if($error) {
					return $this->respondError($error, 422);
				} else {
					/**
					 * Create new row in order table if the order is not for plan activation
					 */
					$order = Order::create( [
						'hash'              => sha1( time() . rand() ),
						'company_id'        => $request->get( 'company' )->id,
						'customer_id'       => $customerId,
						'shipping_fname'    => $request->get('shipping_fname') ?: $customer->billing_fname,
						'shipping_lname'    => $request->get('shipping_lname') ?: $customer->billing_lname,
						'shipping_address1' => $request->get('shipping_address1') ?: $customer->billing_address1,
						'shipping_address2' => $request->get('shipping_address2') ?: $customer->billing_address2,
						'shipping_city'     => $request->get('shipping_city') ?: $customer->billing_city,
						'shipping_state_id' => $request->get('shipping_state_id') ?: $customer->billing_state_id,
						'shipping_zip'      => $request->get('shipping_zip') ?: $customer->billing_zip
					] );

					if($request->has('billing_state_id')) {
						$customerData['billing_state_id'] = $request->get('billing_state_id');
						$customerData['billing_fname'] = $request->get('billing_fname');
						$customerData['billing_lname'] = $request->get('billing_lname');
						$customerData['billing_address1'] = $request->get('billing_address1');
						$customerData['billing_address2'] = $request->get('billing_address2');
						$customerData['billing_city'] = $request->get('billing_city');
						$customerData['billing_zip'] = $request->get('billing_zip');
						$customer->update($customerData);
					}
					foreach ($subscriptionOrders as $subscriptionOrder){
						$orderGroup = OrderGroup::create( [
							'order_id'      => $order->id,
							'requested_zip' => $subscriptionOrder['zip_code']
						] );
						if($orderGroup) {
							/**
							 * @internal Adding the for activation status in the subscription table
							 */
							$subscriptionOrder['subscription_status'] = Subscription::STATUS['for-activation'];
							$subscriptionOrder['sim_type'] = $sim->name;
							$subscriptionOrder['plan_id'] = $planId;
							$outputOrderItem = $this->insertOrderGroupForBulkOrder( $subscriptionOrder, $order, $orderGroup );

							/**
							 * Updating the subscription id in the customer standalone sim table
							 */
							if($outputOrderItem['subscription_id'] && $simId) {
								$customerStandAloneSim = CustomerStandaloneSim::where( [
									[ 'sim_id', $simId ],
									[ 'customer_id', $customer->id ],
									[ 'status', CustomerStandaloneSim::STATUS['complete'] ],
									[ 'sim_num', trim($outputOrderItem['sim_num']) ]
								] )->whereNull('subscription_id')->first();
								if($customerStandAloneSim) {
									$customerStandAloneSim->update( [
										'subscription_id' => $outputOrderItem[ 'subscription_id' ]
									] );
								} else {
									Log::info('Customer Standalone sim record not found for SIM id '. $simId, 'Error in CSV order subscriptions');
								}
							}
						}
					}
				}
				$successResponse = [
					'status'  => 'success',
					'data'    => [
						'order_hash'    => $order->hash
					],
					'message' => 'Subscription order created successfully'
				];
				return $this->respond($successResponse);
			} else {
				Log::info('CSV File not uploaded', 'Error in CSV order subscriptions');
				return $this->respondError('CSV File not uploaded');
			}

		} catch(\Exception $e) {
			Log::info($e->getMessage(), 'Error in CSV order subscriptions');
			return $this->respondError($e->getMessage());
		}
	}


	/**
	 * Order subscription
	 * @param Request $request
	 *
	 * @return \Illuminate\Http\JsonResponse|void
	 */
	public function orderSubscriptions(Request $request)
	{
		try {
			$requestCompany = $request->get('company');

			$validator = Validator::make( $request->all(), [
				'sim_numbers'           =>  'required',
				'plan_id'               =>  [
					'numeric',
					'required',
					Rule::exists('plan', 'id')->where(function ($query) use ($requestCompany) {
						return $query->where('company_id', $requestCompany->id);
					}),
					Rule::exists('customer_products', 'product_id')->where(function ($query) {
						return $query->where('product_type', CustomerProduct::PRODUCT_TYPES['plan']);
					})
				],
				'customer_id'           => [
					'numeric',
					'required',
					Rule::exists('customer', 'id')->where(function ($query) use ($requestCompany) {
						return $query->where('company_id', $requestCompany->id);
					})
				],
				'sim_id'               =>  [
					'numeric',
					'required',
					Rule::exists('sim', 'id')->where(function ($query) use ($requestCompany) {
						return $query->where('company_id', $requestCompany->id);
					}),
					Rule::exists('customer_products', 'product_id')->where(function ($query) {
						return $query->where('product_type', CustomerProduct::PRODUCT_TYPES['sim']);
					})
				],
				'addons.*.addon_id.*'      => [
					'numeric',
					Rule::exists('addon', 'id')->where(function ($query) use ($requestCompany) {
						return $query->where('company_id', $requestCompany->id);
					})
				]
			]);

			if ($validator->fails()) {
				$errors = $validator->errors();
				return $this->respondError($errors->messages(), 422);
			}

			$simNumbers = $request->post('sim_numbers');
			$customerId = $request->get('customer_id');

			$addons = $request->post('addons');

			$customer = Customer::find($customerId);

			$error = [];

			if ($simNumbers) {
				$simNumbers = explode(PHP_EOL, $simNumbers);

				foreach ($simNumbers as $rowIndex => $simNumber) {
					$simNumber = trim( $simNumber );
					$rowNumber = $rowIndex + 1;

					if ( !$this->simNumberExistsForCustomer( $simNumber, $customer ) ) {
						$error[] = "Phone number {$simNumber} is not valid for row $rowNumber";
					}
				}

				if($error) {
					return $this->respondError( $error, 422 );
				} else {
					/**
					 * Create new row in order table if the order is not for plan activation
					 */
					$order = Order::create( [
						'hash'              => sha1( time() . rand() ),
						'company_id'        => $request->get( 'company' )->id,
						'customer_id'       => $customerId,
						'shipping_fname'    => $request->get( 'shipping_fname' ) ?: $customer->billing_fname,
						'shipping_lname'    => $request->get( 'shipping_lname' ) ?: $customer->billing_lname,
						'shipping_address1' => $request->get( 'shipping_address1' ) ?: $customer->billing_address1,
						'shipping_address2' => $request->get( 'shipping_address2' ) ?: $customer->billing_address2,
						'shipping_city'     => $request->get( 'shipping_city' ) ?: $customer->billing_city,
						'shipping_state_id' => $request->get( 'shipping_state_id' ) ?: $customer->billing_state_id,
						'shipping_zip'      => $request->get( 'shipping_zip' ) ?: $customer->billing_zip
					] );

					if ( $request->has( 'billing_state_id' ) ) {
						$customerData[ 'billing_state_id' ] = $request->get( 'billing_state_id' );
						$customerData[ 'billing_fname' ]    = $request->get( 'billing_fname' );
						$customerData[ 'billing_lname' ]    = $request->get( 'billing_lname' );
						$customerData[ 'billing_address1' ] = $request->get( 'billing_address1' );
						$customerData[ 'billing_address2' ] = $request->get( 'billing_address2' );
						$customerData[ 'billing_city' ]     = $request->get( 'billing_city' );
						$customerData[ 'billing_zip' ]      = $request->get( 'billing_zip' );
						$customer->update( $customerData );
					}

					$simId = $request->get( 'sim_id' );

					$sim = Sim::find( $simId );
					foreach ( $simNumbers as $simNumberKey => $simNumber ) {

						$orderGroup        = OrderGroup::create( [
							'order_id'      => $order->id,
							'requested_zip' => $request->get( 'zip_code' )
						] );
						$subscriptionOrder = [
							'sim_type'            => $sim->name,
							'sim_num'             => trim( $simNumber ),
							'plan_id'             => $request->get( 'plan_id' ),
							'zip_code'            => $request->get( 'zip_code' ),
							'subscription_status' => Subscription::STATUS[ 'for-activation' ]
						];

						if($addons && isset($addons[$simNumberKey])) {
							$subscriptionOrder['addon_id'] = $addons[$simNumberKey]['addon_id'];
						}

						if ( $orderGroup ) {
							$outputOrderItem = $this->insertOrderGroupForBulkOrder( $subscriptionOrder, $order, $orderGroup );

							/**
							 * Updating the subscription id in the customer standalone sim table
							 */
							if ( $outputOrderItem[ 'subscription_id' ] && $simId ) {
								$customerStandAloneSim = CustomerStandaloneSim::where( [
									[ 'sim_id', $simId ],
									[ 'customer_id', $customer->id ],
									[ 'status', CustomerStandaloneSim::STATUS[ 'complete' ] ],
									[ 'sim_num', trim( $simNumber ) ]
								] )->whereNull( 'subscription_id' )->first();
								if ( $customerStandAloneSim ) {
									$customerStandAloneSim->update( [
										'subscription_id' => $outputOrderItem[ 'subscription_id' ]
									] );
								} else {
									Log::info( 'Customer Standalone sim record not found for SIM id ' . $simId, 'Error in order subscriptions' );
								}
							}

						}
					}

					$successResponse = [
						'status'  => 'success',
						'data'    => [
							'order_hash' => $order->hash
						],
						'message' => 'Subscription order created successfully'
					];

					return $this->respond( $successResponse );
				}
			} else {
				Log::info('Sim Numbers not added', 'Error in order subscriptions');
				return $this->respondError('Sim Numbers not added');
			}
		} catch(\Exception $e) {
			Log::info($e->getMessage(), 'Error in order subscriptions');
			return $this->respondError($e->getMessage());
		}
	}

	/**
	 * @param Request $request
	 *
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function getOrders(Request $request) {
		try {
			$perPage = $request->has('per_page') ? (int) $request->get('per_page') : 10;
			$requestCompany = $request->get( 'company' );

			$validator = Validator::make( $request->all(), [
				'customer_id' => [
					'numeric',
					'required',
					Rule::exists( 'customer', 'id' )->where( function ( $query ) use ( $requestCompany ) {
						return $query->where( 'company_id', $requestCompany->id );
					} )
				]
			] );

			if ( $validator->fails() ) {
				$errors = $validator->errors();

				return $this->respondError( $errors->messages(), 422 );
			}

			$output = [];

			$orders = Order::where('status', '1')->where('customer_id', $request->get('customer_id'))
			                                     ->with( 'subscriptions', 'standAloneDevices', 'standAloneSims', 'invoice')
			     ->whereHas( 'customer', function ( $query ) use ($requestCompany){
				     $query->where( 'company_id', '=', $requestCompany->id );
			     } )->orderBy('created_at', 'DESC')->paginate($perPage);

			foreach($orders as $order){
				$output[] = [
					'hash'                  => $order->hash,
					'status'                => $order->status,
					'order_num'             => $order->order_num,
					'created_at'            => $order->created_at,
					'items'                 => $this->getOrderProducts($order),
					'total'                 => $order->invoice ? $order->invoice->cal_total_charges : null
				];
			}

			$successResponse = [
				'status'  => 'success',
				'data'    => $output
			];
			return $this->respond($successResponse);

		} catch(\Exception $e) {
			Log::info($e->getMessage(), 'Error in get orders');
			return $this->respondError($e->getMessage());
		}
	}

	/**
	 * @param Order $order
	 *
	 * @return array
	 */
	private function getOrderProducts(Order $order)
	{
		$products = [];
		$standAloneSims = $order->standAloneSims()->get();
		$standAloneDevices = $order->standAloneDevices()->get();
		$subscriptions = $order->subscriptions()->get();

		$groupedSims = $standAloneSims->groupBy(function($standAloneSim) {
			return $standAloneSim->sim_id;
		});

		$groupedDevices = $standAloneDevices->groupBy(function($standAloneDevice) {
			return $standAloneDevice->device_id;
		});

		$groupedSubscriptions = $subscriptions->groupBy(function($subscription) {
			return $subscription->plan_id;
		});

		if($groupedSims->count() > 0) {
			foreach($groupedSims as $simId => $sim) {
				$simRecord = Sim::find($simId);
				$products[] = [
					'product_type' => 'SIM',
					'product_name' => $simRecord->name,
					'quantity'     => $sim->count()
				];
			}
		}

		if($groupedDevices->count() > 0) {
			foreach($groupedDevices as $deviceId => $device) {
				$deviceRecord = Device::find($deviceId);
				$products[] = [
					'product_type' => 'Device',
					'product_name' => $deviceRecord->name,
					'quantity'     => $device->count()
				];
			}
		}

		if($groupedSubscriptions->count() > 0) {
			foreach($groupedSubscriptions as $subscriptionId => $subscription) {
				$subscriptionRecord = Subscription::find($subscriptionId);
				$products[] = [
					'product_type' => 'Subscription',
					'product_name' => $subscriptionRecord->plans->name,
					'quantity'     => $subscription->count()
				];
			}
		}
		return $products;
	}

	/**
	 * @param          $simNumber
	 * @param Customer $customer
	 *
	 * @return mixed
	 */
	protected function simNumberExistsForCustomer($simNumber, Customer $customer) {
		return CustomerStandaloneSim::where([
			['status', '!=', CustomerStandaloneSim::STATUS['closed']],
			['sim_num', $simNumber],
			['customer_id', $customer->id]
		])->exists();
	}

	/**
	 * @param Request $request
	 *
	 * @return \Illuminate\Http\JsonResponse|void
	 * @return \Illuminate\Http\JsonResponse|void
	 */
	public function generateOneTimeInvoice(Request $request) {
		try {
			$requestCompany = $request->get( 'company' );

			$planActivation = $request->get( 'plan_activation' ) ?: false;
			$numberChange = $request->get( 'number_change' ) ?: false;

			$validator = Validator::make( $request->all(), [
				'customer_id' => [
					'numeric',
					'required',
					Rule::exists( 'customer', 'id' )->where( function ( $query ) use ( $requestCompany ) {
						return $query->where( 'company_id', $requestCompany->id );
					} )
				],
				'order_hash'  => [
					'required',
					Rule::exists( 'order', 'hash' )->where( function ( $query ) use ( $requestCompany ) {
						return $query->where( 'company_id', $requestCompany->id );
					} )
				]
			] );

			if ( $validator->fails() ) {
				$errors = $validator->errors();

				return $this->respondError( $errors->messages(), 422 );
			}

			$order = Order::where( 'hash', $request->order_hash )->first();

			$hasSubscription = (bool) $planActivation;

			$itemStatus = ! $planActivation ? 'shipping' : null;

			if ( $order ) {
				$orderGroups = $order->orderGroup()->get();
				if ( ! $planActivation ) {
					foreach ( $orderGroups as $orderGroup ) {
						if ( $orderGroup->subscription_id ) {
							$hasSubscription = true;
							break;
						}
					}
				}
				$this->createInvoice( $request, $order, $orderGroups, $planActivation, $hasSubscription, $itemStatus, 'Bulk Order', $numberChange );

				$outputOrder = [
					'order_hash'            => $order->hash,
					'status'                => $order->status,
					'order_num'             => $order->order_num,
					'total'                 => $order->invoice ? $order->invoice->cal_total_charges : null
				];

				$successResponse = [
					'status'  => 'success',
					'data'    => $outputOrder
				];
				return $this->respond($successResponse);
			} else {
				return $this->respondError( 'Order not found', 404 );
			}
		} catch ( \Exception $e ) {
			Log::info( $e->getMessage(), 'Error in create bulk one time invoice' );

			return $this->respondError( $e->getMessage() );
		}
	}


	/**
	 * @param Request $request
	 *
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function closeLines(Request $request)
	{
		$requestCompany = $request->get('company');

		$customerId = $request->get('customer_id');
		try{
			$validation = Validator::make($request->all(), [
				'customer_id'           => [
					'numeric',
					'required',
					Rule::exists('customer', 'id')->where(function ($query) use ($requestCompany) {
						return $query->where('company_id', $requestCompany->id);
					})
				],
				'sim_num'              => [
					'required',
					'min:11',
					'max:20',
					'distinct',
					Rule::exists('subscription', 'sim_card_num')->where(function ($query) use ($requestCompany, $customerId) {
						return $query->where('status', '!=', Subscription::STATUS['closed'])
						             ->where('company_id', $requestCompany->id)
									 ->where('customer_id', $customerId);
					})
				]],
				[
					'sim_num.exists'       => 'The sim with number :input is not assigned',
				]
			);

			if ( $validation->fails() ) {
				$errors = $validation->errors();
				$validationErrorResponse = [
					'status' => 'error',
					'data'   => $errors->messages()
				];
				return $this->respond($validationErrorResponse, 422);
			}

			$activeSubscription = Subscription::where([
				['status', '!=', Subscription::STATUS['closed']],
				['sim_card_num', $request->get('sim_num')]
			])->first();
			if($activeSubscription) {
				/**
				 * @internal Remove the subscription id from the standalone tables
				 * @since V1.0.69
				 */
				if($activeSubscription->plans->carrier && $activeSubscription->plans->carrier->slug == 'at&t2') {
					CustomerStandAloneSim::where( 'subscription_id', $activeSubscription->id )->update( [ 'subscription_id' => null ] );
					CustomerStandaloneDevice::where( 'subscription_id', $activeSubscription->id )->update( [ 'subscription_id' => null ] );
				}
				$activeSubscription->update([
					'status'        => Subscription::STATUS['closed'],
					'sub_status'    => Subscription::SUB_STATUSES['confirm-closing'],
					'closed_date'   => Carbon::now()
				]);
			}

			$successResponse = [
				'status'    => 'success',
				'data'      => 'Lines closed successfully for sim number '. $request->get('sim_num')
			];
			return $this->respond($successResponse);
		} catch(\Exception $e) {
			Log::info( $e->getMessage(), 'Close Subscription for bulk order' );
			$response = [
				'status' => 'error',
				'data'   => $e->getMessage()
			];
			return $this->respondError( $response );
		}
	}

	/**
	 * @param Request $request
	 *
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function validateZipCodeForUltraSims(Request $request)
	{
		$requestCompany = $request->get('company');
		try {
			$validator = Validator::make( $request->all(), [
				'zip_code' => [
					'required',
					'regex:/^(?:(\d{5})(?:[ \-](\d{4}))?)$/i',
				]
			] );
			if ( $validator->fails() ) {
				$errors                  = $validator->errors();
				$validationErrorResponse = [
					'status'    => 'error',
					'data'      => false,
					'message'   => $errors->messages()
				];

				return $this->respond( $validationErrorResponse, 422 );
			}

			$isZipCodeValidInUltra = $this->isZipCodeValidInUltra($request->get('zip_code'), $requestCompany);

			$successResponse = [
				'status'    => 'success',
				'data'      => $isZipCodeValidInUltra,
				'message'   => $isZipCodeValidInUltra ? 'Zip code is valid' : 'Zip code is not valid'
			];

			return $this->respond( $successResponse );
		} catch( \Exception $e ) {
			Log::info( $e->getMessage(), 'Error of Zip Code validation' );
			$response = [
				'status'    => 'error',
				'data'      => false,
				'message'   => 'Zip code is not valid'
			];

			return $this->respondError( $response );
		}
	}

	/**
	 * @param Request $request
	 *
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function getNumberChangeAddons(Request $request)
	{
		try {
			$requestCompany = $request->get( 'company' );
			$validator = Validator::make( $request->all(), [
				'customer_id'           => [
					'numeric',
					'required',
					Rule::exists('customer', 'id')->where(function ($query) use ($requestCompany) {
						return $query->where('company_id', $requestCompany->id);
					})
				]
			]);

			if ($validator->fails()) {
				$errors = $validator->errors();
				return $this->respondError($errors->messages(), 422);
			}

			$customerProducts = CustomerProduct::where('customer_id', $request->get('customer_id'))
			                                   ->where('product_type', CustomerProduct::PRODUCT_TYPES['addon'])
			                                   ->pluck('product_id')->toArray();

			if($customerProducts) {
				$addons = Addon::whereIn( 'id', $customerProducts )->oneTime()->get();

				return $this->respond( $addons );
			} else {
				return $this->respond( [] );
			}

		} catch(\Exception $e) {
			Log::info($e->getMessage(), 'Error in Addons For Number Change');
			return $this->respondError($e->getMessage());
		}
	}

	/**
	 * @param Request $request
	 *
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function listEligibleSimsForNumberChange(Request $request)
	{
		try {
			$requestCompany = $request->get( 'company' );
			$validator = Validator::make( $request->all(), [
				'customer_id'           => [
					'numeric',
					'required',
					Rule::exists('customer', 'id')->where(function ($query) use ($requestCompany) {
						return $query->where('company_id', $requestCompany->id);
					})
				],
				'addon_id'           => [
					'numeric',
					'required',
					Rule::exists('addon', 'id')->where(function ($query) use ($requestCompany) {
						return $query->where('company_id', $requestCompany->id);
					})
				]
			]);

			if ($validator->fails()) {
				$errors = $validator->errors();
				return $this->respondError($errors->messages(), 422);
			}

			$planIds = PlanToAddon::where('addon_id', $request->get('addon_id'))->pluck('plan_id')->toArray();

			$perPage = $request->has('per_page') ? (int) $request->get('per_page') : 10;

			$subscriptions = Subscription::where('customer_id', $request->get('customer_id'))
										 ->whereIn( 'plan_id', $planIds )
										 ->where( 'status', Subscription::STATUS['active'] )
										 ->where( 'pending_number_change', 0 )
			                             ->whereHas( 'customer', function ( $query ) use ($requestCompany){
				                             $query->where( 'company_id', '=', $requestCompany->id );
			                             } )->orderBy('updated_at', 'DESC')->paginate($perPage);

			return $this->respond($subscriptions);

		} catch(\Exception $e) {
			Log::info($e->getMessage(), 'Error in List Eligible Sims For Number Change');
			return $this->respondError($e->getMessage());
		}

	}

	/**
	 * @param Request $request
	 *
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function getPendingNumberChanges(Request $request)
	{
		try {
			$perPage = $request->has('per_page') ? (int) $request->get('per_page') : 10;
			$requestCompany = $request->get( 'company' );

			$validator = Validator::make( $request->all(), [
				'customer_id' => [
					'numeric',
					Rule::exists( 'customer', 'id' )->where( function ( $query ) use ( $requestCompany ) {
						return $query->where( 'company_id', $requestCompany->id );
					} )
				]
			] );

			if ( $validator->fails() ) {
				$errors = $validator->errors();

				return $this->respondError( $errors->messages(), 422 );
			}

			if($request->has('customer_id')) {

				$subscriptions = Subscription::pendingNumberChange()->where( 'customer_id', $request->get( 'customer_id' ) )
				                             ->whereHas( 'customer', function ( $query ) use ( $requestCompany ) {
					                             $query->where( 'company_id', '=', $requestCompany->id );
				                             } )->with( [ 'subscriptionLogs' => function ( $query ) {
													$query->orderBy( 'created_at', 'DESC' );
												}
											] )->orderBy( 'updated_at', 'DESC' )->paginate( $perPage );
			} else {
				$subscriptions = Subscription::pendingNumberChange()->where( 'company_id', $requestCompany->id  )
				                                                    ->with( [ 'subscriptionLogs' => function ( $query ) {
																		$query->orderBy( 'created_at', 'DESC' );
																	} ] )->orderBy( 'updated_at', 'DESC' )->paginate( $perPage );
			}
			return $this->respond($subscriptions);

		} catch(\Exception $e) {
			Log::info($e->getMessage(), 'Error in get pending number changes');
			return $this->respondError($e->getMessage());
		}

	}


	/**
	 * @param Request $request
	 *
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function numberChangeHistory(Request $request) {
		try {
			$perPage        = $request->has( 'per_page' ) ? (int) $request->get( 'per_page' ) : 10;
			$requestCompany = $request->get( 'company' );

			$validator = Validator::make( $request->all(), [
				'customer_id' => [
					'numeric',
					'required',
					Rule::exists( 'customer', 'id' )->where( function ( $query ) use ( $requestCompany ) {
						return $query->where( 'company_id', $requestCompany->id );
					} )
				]
			] );

			if ( $validator->fails() ) {
				$errors = $validator->errors();
				return $this->respondError( $errors->messages(), 422 );
			}

			$subscriptionLogs = SubscriptionLog::where('customer_id', $request->get('customer_id'))
										->whereIn('category', [ SubscriptionLog::CATEGORY['number-change-requested'], SubscriptionLog::CATEGORY['number-change-processed'] ])
			                            ->whereHas( 'customer', function ( $query ) use ($requestCompany) {
				                            $query->where( 'company_id', '=', $requestCompany->id );
			                            })->orderBy('created_at', 'DESC')->paginate($perPage);

			return $this->respond($subscriptionLogs);

		} catch ( \Exception $e ) {
			Log::info( $e->getMessage(), 'Error in number change history' );

			return $this->respondError( $e->getMessage() );
		}
	}

	/**
	 * @param Request $request
	 *
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function processNumberChange(Request $request) {
		try {
			$requestCompany = $request->get( 'company' );

			$subscription_id = $request->input('subscription_id');

			$validator = Validator::make( $request->all(), [
				'customer_id' => [
					'numeric',
					'required',
					Rule::exists( 'customer', 'id' )->where( function ( $query ) use ( $requestCompany ) {
						return $query->where( 'company_id', $requestCompany->id );
					} )
				],
				'subscription_id'   => [
					'numeric',
					'required',
					Rule::exists( 'subscription', 'id' )->where( function ( $query ) use ( $requestCompany ) {
						return $query->where( 'company_id', $requestCompany->id );
					} )
				],
				'phone_number'   => [
					'required',
					Rule::unique('subscription')->ignore($subscription_id)->where(function ($query) use ($requestCompany) {
						return $query->where([
							[ 'status', '!=', Subscription::STATUS['closed'] ],
							[ 'company_id', $requestCompany->id ]
						]);
					})
				]
			] );

			if ( $validator->fails() ) {
				$errors = $validator->errors();

				return $this->respondError( $errors->messages(), 422 );
			}

			$subscription = Subscription::find($request->get('subscription_id'));

			$existingPhoneNumber = $subscription->phone_number;

			$subscription = tap($subscription, function($subscription) use ($request) {
				$subscription->pending_number_change = 0;
				$subscription->phone_number = preg_replace('/[^\dxX]/', '', $request->get('phone_number'));
				$subscription->save();
			});

			$subscriptionLog = $subscription->subscriptionLogs()->where([
					[ 'category', SubscriptionLog::CATEGORY['number-change-requested' ]],
					[ 'old_product', $existingPhoneNumber ]
				])->first();
			$subscription->subscriptionLogs()->create([
				'subscription_id'   => $subscription->id,
				'company_id'        => $subscription->company->id,
				'customer_id'       => $subscription->customer->id,
				'description'       => $subscription->sim_card_num,
				'category'          => SubscriptionLog::CATEGORY['number-change-processed'],
				'old_product'       => $existingPhoneNumber,
				'new_product'       => $subscription->phone_number,
				'order_num'         => $subscriptionLog->order_num ?? null,
			]);

			$successResponse = [
				'status'    => 'success',
				'data'      => $subscription,
				'message'   => 'Number change processed successfully'
			];

			return $this->respond($successResponse);
		} catch ( \Exception $e ) {
			Log::info( $e->getMessage(), 'Error in process number change' );

			return $this->respondError( $e->getMessage() );
		}

	}


	/**
	 * Order Number Changes from CSV file
	 * @param Request $request
	 *
	 * @return \Illuminate\Http\JsonResponse|void
	 */
	public function csvOrderNumberChanges(Request $request)
	{
		try {
			$requestCompany = $request->get('company');

			$validator = Validator::make( $request->all(), [
				'csv_file'              =>  'required',
				'customer_id'           => [
					'numeric',
					'required',
					Rule::exists('customer', 'id')->where(function ($query) use ($requestCompany) {
						return $query->where('company_id', $requestCompany->id);
					})
				],
				'addon_id'              => [
					'numeric',
					'required',
					Rule::exists('addon', 'id')->where(function ($query) use ($requestCompany) {
						return $query->where('company_id', $requestCompany->id)->where('is_one_time', true);
					}),
					Rule::exists('customer_products', 'product_id')->where(function ($query) use ($request) {
						return $query->where('customer_id', $request->get('customer_id'))
						             ->where('product_type', CustomerProduct::PRODUCT_TYPES['addon']);
					})

				]
			]);

			if ($validator->fails()) {
				$errors = $validator->errors();
				return $this->respondError($errors->messages(), 422);
			}
			$customerId = $request->get('customer_id');

			$customer = Customer::find($customerId);

			$csvFile = $request->post('csv_file');

			$addonId = $request->get( 'addon_id' );

			/**
			 * Validate if the input file is CSV file
			 */
			if (preg_match('/^data:text\/(\w+);base64,/', $csvFile) || preg_match('/^data:application\/(\w+);base64,/', $csvFile) || preg_match('/^data:@file\/(\w+);base64,/', $csvFile)) {
				$csvFile = substr($csvFile, strpos($csvFile, ',') + 1);
				$csvFile = base64_decode($csvFile);
			} else {
				return $this->respondError('CSV file not uploaded', 422);
			}

			if ($csvFile) {
				$csvAsArray = str_getcsv( $csvFile, "\n" );
				$headerRows = array_shift( $csvAsArray );
				$headerRowsArray = explode( ',', $headerRows );
				$csvAsArray = array_map( function ( $row ) use ( $headerRowsArray ) {
					return array_combine( $headerRowsArray, str_getcsv( $row ) );
				}, $csvAsArray );

				$numberChangesData = [];
				$error = [];

				foreach ( $csvAsArray as $rowIndex => $row ) {
					$rowNumber = $rowIndex + 1;


					if(!$row['phone_number']) {
						$error[] = "Required fields missing for row $rowNumber";
					}

					/**
					 * Validate if the required fields are present
					 */
					if($row['zip_code'] && !$this->isZipCodeValid($row['zip_code'], $requestCompany)) {
						$error[] = "Zip code {$row['zip_code']} is not valid for row $rowNumber";
					}

					$row['phone_number'] = str_replace("'", '', $row['phone_number']);

					$subscription = $this->subscriptionExistsForCustomer($row['phone_number'], $customer, $requestCompany, $addonId);


					if(!$subscription) {
						$error[] = "Number Change Request for Phone Number {$row['phone_number']} is not valid for row $rowNumber";
					} else {

						/*
						 * Get the subscription_id from the old_num from number change subscription log
						 */
						$row[ 'subscription_id' ] = $subscription->id;
						$row[ 'addon_id' ] = $addonId;
						unset( $row[ 'phone_number' ] );

						$numberChangesData[] = $row;
					}
				}

				if($error) {
					return $this->respondError($error, 422);
				} else {

					$orderTransaction = DB::transaction( function () use ( $request, $numberChangesData, $customer ) {

						/**
						 * Create new row in order table if the order is not for plan activation
						 */
						$order = Order::create( [
							'hash'              => sha1( time() . rand() ),
							'company_id'        => $request->get( 'company' )->id,
							'customer_id'       => $request->get( 'customer_id' ),
							'shipping_fname'    => $request->get( 'shipping_fname' ) ?: $customer->billing_fname,
							'shipping_lname'    => $request->get( 'shipping_lname' ) ?: $customer->billing_lname,
							'shipping_address1' => $request->get( 'shipping_address1' ) ?: $customer->billing_address1,
							'shipping_address2' => $request->get( 'shipping_address2' ) ?: $customer->billing_address2,
							'shipping_city'     => $request->get( 'shipping_city' ) ?: $customer->billing_city,
							'shipping_state_id' => $request->get( 'shipping_state_id' ) ?: $customer->billing_state_id,
							'shipping_zip'      => $request->get( 'shipping_zip' ) ?: $customer->billing_zip
						] );

						if ( $request->has( 'billing_state_id' ) ) {
							$customerData[ 'billing_state_id' ] = $request->get( 'billing_state_id' );
							$customerData[ 'billing_fname' ]    = $request->get( 'billing_fname' );
							$customerData[ 'billing_lname' ]    = $request->get( 'billing_lname' );
							$customerData[ 'billing_address1' ] = $request->get( 'billing_address1' );
							$customerData[ 'billing_address2' ] = $request->get( 'billing_address2' );
							$customerData[ 'billing_city' ]     = $request->get( 'billing_city' );
							$customerData[ 'billing_zip' ]      = $request->get( 'billing_zip' );
							$customer->update( $customerData );
						}

						foreach ( $numberChangesData as $numberChangesDataItem ) {
							$order_group = OrderGroup::create( [
								'order_id'      => $order->id,
								'requested_zip' => $numberChangesDataItem[ 'zip_code' ]
							] );
							/**
							 * Transforming the addon_id from csv to array to insert in order_group table
							 */
							$numberChangesDataItem[ 'addon_id' ] = [$numberChangesDataItem[ 'addon_id' ]];

							if ( $order_group ) {
								$this->insertOrderGroupForBulkOrder( $numberChangesDataItem, $order, $order_group );
							}
						}

						return $order;
					} );
					$successResponse  = [
						'status'  => 'success',
						'data'    => [
							'order_hash' => $orderTransaction ? $orderTransaction->hash : null
						],
						'message' => 'Order created successfully'
					];

					return $this->respond( $successResponse );
				}
			} else {
				Log::info('CSV File not uploaded', 'Error in CSV number change');
				return $this->respondError('CSV File not uploaded');
			}

		} catch(\Exception $e) {
			Log::info($e->getMessage(), 'Error in CSV number change');
			return $this->respondError($e->getMessage());
		}
	}

	/**
	 * @param          $phoneNumber
	 * @param Customer $customer
	 * @param Company  $requestCompany
	 * @param          $addonId
	 *
	 * @return false
	 */
	private function subscriptionExistsForCustomer($phoneNumber, Customer $customer, Company $requestCompany, $addonId){
		$planIds = PlanToAddon::where('addon_id', $addonId)->pluck('plan_id')->toArray();
		$subscription = Subscription::where( 'status', Subscription::STATUS['active'] )
		                            ->where( 'pending_number_change', 0 )
		                            ->where( 'phone_number', $phoneNumber )
									->whereIn( 'plan_id', $planIds )
		                            ->whereHas( 'customer', function ( $query ) use ( $requestCompany, $customer) {
										$query->where(
											[
												[ 'id', $customer->id],
												[ 'company_id', $requestCompany->id ]
											]
										);
									})->first();
		return $subscription && $subscription->company_id === $requestCompany->id && $subscription->customer_id === $customer->id ? $subscription : false;
	}
}