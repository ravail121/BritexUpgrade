<?php

namespace App\Http\Controllers\Api\V1\BulkOrder;

use Validator;
use App\Model\Sim;
use App\Model\Plan;
use App\Helpers\Log;
use App\Model\Order;
use App\Model\Customer;
use App\Model\OrderGroup;
use App\Rules\ValidZipCode;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use App\Model\CustomerStandaloneSim;
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

			$perPage = $request->has('per_page') ? (int) $request->get('per_page') : 5;

			$sims = Sim::where( [
				['company_id', $requestCompany->id],
				['show', self::SHOW_COLUMN_VALUES['visible-and-orderable']],
				['carrier_id', 5]
			] )->paginate($perPage);

			return $this->respond($sims);

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
			$validation = $this->validationRequestForBulkOrder($request, $planActivation);
			if($validation !== 'valid') {
				return $validation;
			}

			$data = $request->all();

			DB::transaction(function () use ($request, $data, $planActivation) {

				$customer = Customer::find($request->get('customer_id'));

				$orderCount = $this->getOrderCount($customer);

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
					'shipping_zip'      => $request->get('shipping_zip') ?: $customer->billing_zip,
					'order_num'         => $orderCount + 1
				] );

				$orderItems = $request->get( 'orders' );

				$outputOrderItems = [];
				$hasSubscription = false;

				foreach ( $orderItems as $orderItem ) {
					$order_group = null;
					$paidMonthlyInvoice = isset( $orderItem[ 'paid_monthly_invoice' ] ) ? $orderItem[ 'paid_monthly_invoice' ] : null;
					$order_group = OrderGroup::create( [
						'order_id' => $order->id
					] );

					if($order_group) {
						$outputOrderItem = $this->insertOrderGroupForBulkOrder( $orderItem, $order, $order_group );
						if ( isset( $paidMonthlyInvoice ) && $paidMonthlyInvoice == "1" && isset( $orderItem[ 'plan_id' ] ) ) {
							$monthly_order_group = OrderGroup::create( [
								'order_id' => $order->id
							] );
							$outputOrderItem = $this->insertOrderGroupForBulkOrder( $orderItem, $order, $monthly_order_group, 1 );
						}
						if(isset($outputOrderItem['subscription_id'])){
							$hasSubscription = true;
						}
						$outputOrderItems[] = $outputOrderItem;
					}
				}
				if($order) {
					$this->createInvoice($request, $order, $outputOrderItems, $planActivation, $hasSubscription, 'shipping');
				}
			});
			$successResponse = [
				'status'  => 'success',
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
			$planActivation = $request->get('plan_activation') ?: false;
			$validation = $this->validationRequestForBulkOrder($request, $planActivation);
			if($validation !== 'valid') {
				return $validation;
			}
			$output = [];
			$orderItems = $request->get( 'orders' );
			$customer = Customer::find($request->get('customer_id'));

			$subTotalWithoutSurcharge = $this->subTotalPriceForPreview($request, $orderItems, false);

			$output['totalPrice'] =  $this->totalPriceForPreview($request, $orderItems);
			$output['subtotalPrice'] = $subTotalWithoutSurcharge;
			$output['monthlyCharge'] = $this->calMonthlyChargeForPreview($orderItems);
			$output['taxes'] = $this->calTaxesForPreview($request, $orderItems);
			$output['regulatory'] = $this->calRegulatoryForPreview($request, $orderItems);
			$output['activationFees'] = $this->getPlanActivationPricesForPreview($orderItems);
			$output['surcharge'] = $this->getSurchargeAmountForPreview($subTotalWithoutSurcharge, $customer);
			$costBreakDown = (object) [
				'devices'   => $this->getCostBreakDownPreviewForDevices($request, $orderItems),
				'plans'     => $this->getCostBreakDownPreviewForPlans($request, $orderItems),
				'sims'      => $this->getCostBreakDownPreviewForSims($request, $orderItems)
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
	 * @param $planActivation
	 *
	 * @return \Illuminate\Http\JsonResponse|string
	 */
	private function validationRequestForBulkOrder($request, $planActivation)
	{
		$requestCompany = $request->get('company');
		$customerId = $request->get('customer_id');
		if ( !$requestCompany->enable_bulk_order ) {
			$notAllowedResponse = [
				'status' => 'error',
				'data'   => 'Bulk Order not enabled for the requesting company'
			];
			return $this->respond($notAllowedResponse, 503);
		}
		if($planActivation){
			/**
			 * @internal When activating a plan, make sure sim is assigned to specified customer
			 */
			$simNumValidation = Rule::exists('customer_standalone_sim', 'sim_num')->where(function ($query) use ($customerId) {
				return $query->where('status', '!=', 'closed')
				             ->where('customer_id', $customerId);
			});
		} else {
			/**
			 * @internal When purchasing a SIM, make sure sim is not assigned to another customer
			 */
			$simNumValidation =  Rule::unique('customer_standalone_sim', 'sim_num')->where(function ($query) {
				return $query->where('status', '!=', 'closed');
			});
		}
		$validation = Validator::make(
			$request->all(),
			[
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
				'orders.*.plan_id'              => [
					'required_with:plan_activation',
					'numeric',
					Rule::exists('plan', 'id')->where(function ($query) use ($requestCompany) {
						return $query->where('company_id', $requestCompany->id)
							->where('show', self::SHOW_COLUMN_VALUES['visible-and-orderable']);
					})
				],
				'orders.*.sim_id'               =>  [
					'numeric',
					Rule::exists('sim', 'id')->where(function ($query) use ($requestCompany) {
						return $query->where('company_id', $requestCompany->id)
									->where('show', self::SHOW_COLUMN_VALUES['visible-and-orderable'])
									->where('carrier_id', 5);
					})
				],
				'orders.*.subscription_id'      => [
					'numeric',
					Rule::exists('subscription', 'id')->where(function ($query) use ($requestCompany) {
						return $query->where('company_id', $requestCompany->id);
					})
				],
				'orders.*.sim_num'              => [
					'required_with:plan_activation',
					'min:11',
					'max:20',
					'distinct',
					Rule::unique('subscription', 'sim_card_num')->where(function ($query)  {
						return $query->where('status', '!=', 'closed');
					}),
					$simNumValidation
				],
				'orders.*.sim_type'             => 'string',
				'orders.*.porting_number'       => 'string',
				'orders.*.area_code'            => 'string',
				'orders.*.operating_system'     => 'string',
				'orders.*.imei_number'          => 'digits_between:14,16',
				'orders.*.subscription_status'  => 'string',
				'shipping_fname'                => 'string',
				'shipping_lname'                => 'string',
				'shipping_address1'             => 'string',
				'shipping_address2'             => 'string',
				'shipping_city'                 => 'string',
				'shipping_state_id'             => 'string',
				'shipping_zip'                  => 'numeric'
			],
			[
				'orders.*.sim_num.unique'       => 'The sim with number :input is already assigned',
				'orders.*.sim_num.exists'       => 'The sim with number :input is not assigned to this customer',
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

			$customer = Customer::where('company_id', $requestCompany->id)->where('id', $request->get('customer_id'))->first();
			$perPage = $request->has('per_page') ? (int) $request->get('per_page') : 25;

			$simId = $request->get('sim_id');

			if($simId) {
				$orderSims = CustomerStandaloneSim::where( [
					[ 'sim_id', $simId ],
					[ 'customer_id', $customer->id ],
					[ 'status', 'shipping'],
					[ 'processed', 0 ],
					[ 'subscription_id', null ]
				] )->whereHas( 'sim', function ( $query ) use ( $requestCompany, $simId ) {
					$query->where(
						[
							[ 'company_id', $requestCompany->id ],
							[ 'show', self::SHOW_COLUMN_VALUES[ 'visible-and-orderable' ] ],
							[ 'carrier_id', 5 ]
						]
					);
				} )->shipping()->with( 'sim' )->paginate( $perPage );
			} else {
				$orderSims = CustomerStandaloneSim::where( [
					[ 'customer_id', $customer->id ],
					[ 'subscription_id', null ],
					[ 'status', 'shipping' ],
					[ 'processed', 0 ]
				] )->whereHas( 'sim', function ( $query ) use ( $requestCompany ) {
					$query->where(
						[
							[ 'company_id', $requestCompany->id ],
							[ 'show', self::SHOW_COLUMN_VALUES[ 'visible-and-orderable' ] ],
							[ 'carrier_id', 5 ]
						]
					);
				} )->shipping()->with( 'sim' )->paginate( $perPage );
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
						return $query->where('company_id', $requestCompany->id);
					})
				]
			]);

			if ($validator->fails()) {
				$errors = $validator->errors();
				return $this->respondError($errors->messages(), 422);
			}

			$sim = Sim::where('id', $request->get('sim_id'))->first();

			$orderPlans = Plan::where( [
				['company_id', $requestCompany->id],
				['show', self::SHOW_COLUMN_VALUES['visible-and-orderable']],
				['carrier_id', $sim->carrier_id]
			] )->get();

			return $this->respond($orderPlans);

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
						return $query->where('company_id', $requestCompany->id)
							/**
							 * Check if the carrier is ultra mobile
							 */
							->where('carrier_id', 5)
							->where('show', self::SHOW_COLUMN_VALUES['visible-and-orderable']);
					})
				],
				'plan_id'               =>  [
					'numeric',
					'required',
					Rule::exists('plan', 'id')->where(function ($query) use ($requestCompany) {
						return $query->where('company_id', $requestCompany->id)
							->where('show', self::SHOW_COLUMN_VALUES['visible-and-orderable']);
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
			$planActivation = false;

			$csvFile = $request->post('csv_file');

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
				foreach ( $csvAsArray as $rowIndex => $row ) {
					if($this->isZipCodeValid($row['zip_code'])) {
						$row['plan_id'] = $request->get('plan_id');
						$row['sim_id'] = $request->get('sim_id');
						$subscriptionOrders[] = $row;
					} else {
						$error[] = 'Zip code is not valid for row ' . $rowIndex;
					}
				}

				if($error) {
					return $this->respondError($error, 422);
				} else {
					$orderCount = $this->getOrderCount($customer);
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
						'shipping_zip'      => $request->get('shipping_zip') ?: $customer->billing_zip,
						'order_num'         => $orderCount + 1
					] );
					$outputOrderItems = [];
					foreach ($subscriptionOrders as $subscriptionOrder){
						$orderGroup = OrderGroup::create( [
							'order_id' => $order->id
						] );
						if($orderGroup) {
							$outputOrderItem = $this->insertOrderGroupForBulkOrder( $subscriptionOrder, $order, $orderGroup );

							$outputOrderItems[] = $outputOrderItem;
						}
					}
				}
				if($order) {
					$this->createInvoice($request, $order, $outputOrderItems, $planActivation, true);
				}
				$successResponse = [
					'status'  => 'success',
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
						return $query->where('company_id', $requestCompany->id)
						->where('show', self::SHOW_COLUMN_VALUES['visible-and-orderable']);
					})
				],
				'zip_code'              => [
					'nullable',
					'regex:/^(?:(\d{5})(?:[ \-](\d{4}))?)$/i',
					new ValidZipCode
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
						return $query->where('company_id', $requestCompany->id)
							/**
						    * Check if the carrier is ultra mobile
						    */
							->where('carrier_id', 5)
					        ->where('show', self::SHOW_COLUMN_VALUES['visible-and-orderable']);
					})
				],
			]);

			if ($validator->fails()) {
				$errors = $validator->errors();
				return $this->respondError($errors->messages(), 422);
			}

			$simNumbers = $request->post('sim_numbers');
			$customerId = $request->get('customer_id');

			$customer = Customer::find($customerId);
			$planActivation = false;

			if ($simNumbers) {
				$simNumbers = explode(PHP_EOL, $simNumbers);
				$orderCount = $this->getOrderCount($customer);
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
					'shipping_zip'      => $request->get('shipping_zip') ?: $customer->billing_zip,
					'order_num'         => $orderCount + 1
				] );
				$outputOrderItems = [];
				foreach ($simNumbers as $simNumber){
					$orderGroup = OrderGroup::create( [
						'order_id' => $order->id
					] );
					$subscriptionOrder = [
						'sim_id'        => $request->get('sim_id'),
						'sim_num'       => $simNumber,
						'plan_id'       => $request->get('plan_id'),
						'zip_code'      => $request->get('zip_code')
					];
					if($orderGroup) {
						$outputOrderItem = $this->insertOrderGroupForBulkOrder( $subscriptionOrder, $order, $orderGroup );

						$outputOrderItems[] = $outputOrderItem;
					}
				}
				if($order) {
					$this->createInvoice($request, $order, $outputOrderItems, $planActivation, true);
				}
				$successResponse = [
					'status'  => 'success',
					'message' => 'Subscription order created successfully'
				];
				return $this->respond($successResponse);
			} else {
				Log::info('Sim Numbers not added', 'Error in order subscriptions');
				return $this->respondError('Sim Numbers not added');
			}

		} catch(\Exception $e) {
			Log::info($e->getMessage(), 'Error in order subscriptions');
			return $this->respondError($e->getMessage());
		}
	}
}