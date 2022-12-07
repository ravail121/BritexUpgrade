<?php

namespace App\Http\Controllers\Api\V1\BulkOrder;


use Validator;
use App\Model\Sim;
use App\Helpers\Log;
use App\Model\Order;
use App\Model\Customer;
use App\Model\OrderGroup;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\BaseController;
use App\Http\Controllers\Api\V1\Traits\BulkOrderTrait;

/**
 * Class OrderController
 *
 * @package App\Http\Controllers\Api\V1
 */
class OrderController extends BaseController
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
			$company = $request->get('company');

			$sims = Sim::whereCompanyId($company->id)->get();

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

				$orderCount = Order::where([['status', 1],['company_id', $customer->company_id]])->max('order_num');

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
					$this->createInvoice($request, $order, $outputOrderItems, $planActivation, $hasSubscription);
				}
			});


			$successResponse = [
				'status'    => 'success',
				'data'      => 'Orders created successfully'
			];
			return $this->respond($successResponse);

		} catch(\Exception $e) {
			Log::info($e->getMessage() . ' on line number: '.$e->getLine() . ' Create Order for Bulk Order');
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
						return $query->where('company_id', $requestCompany->id);
					})
				],
				'orders.*.sim_id'               =>  [
					'numeric',
					'required_with:orders.*.sim_num',
					Rule::exists('sim', 'id')->where(function ($query) use ($requestCompany) {
						return $query->where('company_id', $requestCompany->id);
					})
				],
				'orders.*.subscription_id'      => [
					'numeric',
					Rule::exists('subscription', 'id')->where(function ($query) use ($requestCompany) {
						return $query->where('company_id', $requestCompany->id);
					})
				],
				'orders.*.sim_num'              => [
					'required_with:orders.*.sim_id',
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
}