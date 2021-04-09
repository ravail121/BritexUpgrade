<?php

namespace App\Http\Controllers\Api\V1;

use Cassandra\Custom;
use Validator;
use Carbon\Carbon;
use App\Model\Sim;
use App\Model\Plan;
use App\Model\Order;
use App\Helpers\Log;
use App\Model\Customer;
use App\Model\OrderGroup;
use App\Model\OrderCoupon;
use App\Model\PlanToAddon;
use App\Model\Subscription;
use Illuminate\Http\Request;
use App\Model\OrderGroupAddon;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use App\Model\BusinessVerification;
use App\Http\Controllers\BaseController;
use App\Http\Controllers\Api\V1\Traits\BulkOrderTrait;
use App\Http\Controllers\Api\V1\Traits\InvoiceCouponTrait;
/**
 * Class OrderController
 *
 * @package App\Http\Controllers\Api\V1
 */
class OrderController extends BaseController
{
    use InvoiceCouponTrait, BulkOrderTrait;

    /**
     *
     */
    const SPECIFIC_TYPES = [
        'PLAN'      =>  1,
        'DEVICE'    =>  2,
        'SIM'       =>  3,
        'ADDON'     =>  4
    ];

    /**
     *
     */
    const FIXED_PERC_TYPES = [
        'fixed'      => 1,
        'percentage' => 2
    ];

    /**
     *
     */
    const PLAN_TYPE = [
        'Voice'     => 1,
        'Data'      => 2
    ];
    /**
     * $cartItems
     *
     * @var array
     */
    protected $cartItems;
    /**
     * $prices
     *
     * @var array
     */
    protected $prices;
    /**
     * $regulatory
     *
     * @var array
     */
    protected $regulatory;
    /**
     * $taxes
     *
     * @var array
     */
    protected $taxes;
    /**
     * $shippingFee
     *
     * @var array
     */
    protected $shippingFee;
    /**
     * $activation
     *
     * @var array
     */
    protected $activation;
	/**
	 * @var
	 */
	protected $tax_id;
	/**
	 * @var
	 */
	protected $tax_total;
	/**
	 * @var
	 */
	protected $total_price;
	/**
	 * @var
	 */
	protected $couponAmount;
	/**
	 * @var
	 */
	protected $taxrate;
	/**
	 * @var
	 */
	protected $order_hash;
	/**
	 * @var int[]
	 */
	protected $totalTaxableAmount = [0];
	/**
	 * @var
	 */
	protected  $cart;


    /**
	 * OrderController constructor.
	 */
	public function __construct(){
        $this->content = array();
    }

	/**
	 * @param Request $request
	 *
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function get(Request $request)
	{

		if($request->has('customer_id')){
			$customerId = $request->input('customer_id');
			$order = Order::where('customer_id', $customerId)
				->where('company_id', $request->get('company')->id)
				->pendingOrders()->first();
			if(!$order){
				$this->content = null;
				return response()->json($this->content);
			}

			$hash = $order->hash;
		} else {
			$hash = $request->input('order_hash');
		}

        $this->order_hash = $hash;
        $do_order_exist_for_company = Order::where('hash', $this->order_hash)
            ->where('company_id', $request->get('company')->id)
            ->exists();
        if(!$do_order_exist_for_company){
            return $this->respondError('Invalid Order Hash', 400);
        }
        $paidMonthlyInvoice = $request->input('paid_monthly_invoice');

        $order =  $ordergroups = $newPlan = [];

        if($hash){
            $order_groups = OrderGroup::with(['order', 'sim', 'device', 'device.device_image'])->whereHas('order', function($query) use ($hash) {
                $query->where('hash', $hash);
            })->get();

            foreach($order_groups as $key => $og){

                if($order == []){

                    $order =  [
                        "id"                     => $og->order->id,
                        "order_hash"             => $og->order->hash,
                        "active_group_id"        => $og->order->active_group_id,
                        "active_subscription_id" => $og->order->active_subscription_id,
                        "order_num"              => $og->order->order_num,
                        "status"                 => $og->order->status,
                        "customer_id"            => $og->order->customer_id,
                        'order_groups'           => [],
                        'business_verification'  => null,
                        'operating_system'       => $og->operating_system,
                        'imei_number'            => $og->imei_number,
	                    /**
	                     * @internal All the sensitive information from the company are excluded
	                     */
                        'company'                => $og->order->company()->exclude([
                        	'api_key',
							'sprint_api_key',
	                        'smtp_driver',
	                        'smtp_host',
	                        'smtp_encryption',
	                        'smtp_port',
	                        'smtp_username',
	                        'smtp_password',
	                        'primary_contact_name',
	                        'primary_contact_phone_number',
	                        'primary_contact_email_address',
	                        'address_line_1',
	                        'address_line_2',
	                        'city',
	                        'state',
	                        'zip',
	                        'usaepay_api_key',
	                        'usaepay_live',
	                        'usaepay_username',
	                        'usaepay_password',
	                        'readycloud_api_key',
	                        'readycloud_username',
	                        'readycloud_password',
	                        'tbc_username',
	                        'tbc_password',
	                        'apex_username',
	                        'apex_password',
	                        'premier_username',
	                        'premier_password',
	                        'opus_username',
	                        'opus_password',
	                        'goknows_api_key',
	                        'ultra_username',
	                        'ultra_password'
                        ])->first(),
                        'customer'               => $og->customer,
                    ];
                }

                $tmp = array(
                    'id'                => $og->id,
                    'sim'               => $og->sim,
                    'sim_num'           => $og->sim_num,
                    'sim_type'          => $og->sim_type,
                    'plan'              => $og->plan,
                    'addons'            => [],
                    'porting_number'    => $og->porting_number,
                    'area_code'         => $og->area_code,
                    'device'            => $og->device_detail,
                    'operating_system'  => $og->operating_system,
                    'imei_number'       => $og->imei_number,
                    'plan_prorated_amt' => $og->plan_prorated_amt,
                    'subscription'      => $og->subscription,
                );

                if(isset($paidMonthlyInvoice) && $paidMonthlyInvoice == "1" && isset($tmp['plan']['id'])){
                    if(in_array($og->plan_id, $newPlan)){
                        $tmp['plan']['amount_onetime'] = 0;
                        $tmp['plan']['auto_generated_plans'] = 1;
                        $order['paid_invoice'] = 1;
                        $newPlan[array_search($og->plan_id, $newPlan)] = null;
                    }else{
                        $newPlan[$key] = $og->plan_id;
                    }
                }

                if(isset($tmp['subscription'])){
                    $tmp['plan']['amount_onetime'] = 0;
                    if($key > 0){
                        $tmp['plan']['from'] = Carbon::parse($og->customer->billing_end)->addDays(1)->toDateString();
                        $tmp['plan']['to'] = Carbon::parse($og->customer->billing_end)->addMonth()->toDateString();
                        $order['paid_invoice'] = 1;
                    }else{
                        $today = Carbon::now();
                        $billingStart = Carbon::parse($og->customer->billing_start)->subDays(1);
                        $tmp['plan']['from'] = $today->toDateString();
                        if($billingStart < $today){
                            $tmp['plan']['to'] = $og->customer->billing_end;
                        }else{
                            $tmp['plan']['to'] = $billingStart->toDateString();
                        }
                    }

                    if ($og->change_subscription == '0') {
                       $tmp['status'] = "SamePlan";
                       $tmp['plan']['amount_onetime'] = 0;
                    }elseif($og->plan->amount_recurring - $og->subscription->plan->amount_recurring >= 0){
                        $tmp['status'] = "Upgrade";
                    }else{
                        $tmp['status'] = "Downgrade";
                        $tmp['plan']['amount_onetime'] = 0;
                    }
                }
                $_addons = OrderGroupAddon::with(['addon'])->where('order_group_id', $og->id )->get();
                foreach ($_addons as $a) {
                    $a['addon'] = array_merge($a['addon']->toArray(), ['prorated_amt' => $a['prorated_amt'], 'subscription_addon_id'=> $a['subscription_addon_id'], 'subscription_id' => $a['subscription_id']]);

                    array_push($tmp['addons'], collect($a['addon']));
                }

                array_push($ordergroups, $tmp);
            }
            if (count($order)) {

                $businessVerification = BusinessVerification::where('order_id', $order['id'])->first();
                $order['business_verification'] = $businessVerification;
            }

        }

        $order['order_groups'] = $ordergroups;
        $this->cartItems = $order;
        $this->getCouponDetails();
        $order['totalPrice'] =  $this->totalPrice();
        $order['subtotalPrice'] =  $this->subTotalPrice();
        $order['activeGroupId'] =  $this->getActiveGroupId();
        $order['monthlyCharge'] =  $this->calMonthlyCharge();
        $order['taxes'] =  $this->calTaxes();
        $order['regulatory'] =  $this->calRegulatory();
        $order['shippingFee'] =  $this->getShippingFee();
        $order['coupons'] =  $this->coupon();
        $order['couponDetails'] =  $this->couponAmount;
        $this->content = $order;
        return response()->json($this->content);
    }


	/**
	 * @param Request $request
	 * @param         $id
	 *
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function find(Request $request, $id)
	{
        $this->content = Order::find($id);
        return response()->json($this->content);
    }


	/**
	 * @param Request $request
	 *
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function post(Request $request)
    {
        $validation = Validator::make(
            $request->all(),
            [
                'order_hash'       => 'string',
                'device_id'        => 'numeric',
                'plan_id'          => 'numeric',
                'sim_id'           => 'numeric',
                'sim_num'          => 'numeric',
                'sim_type'         => 'string',
                // 'addon_id'         => 'numeric',
                'subscription_id'  => 'numeric',
                'porting_number'   => 'string',
                'area_code'        => 'string',
                'operating_system' => 'string',
                'imei_number'      => 'digits_between:14,16',
            ]
        );

        if($request->has('sim_num')) {
	        $validation->after( function ( $validator ) use ($request) {
		        if ( $this->validateIfTheSimIsUsed( $request->input('sim_num') ) ) {
			        $validator->errors()->add( 'sim_num', "The SIM can't be used." );
		        }
	        } );
        }


        if ($validation->fails()) {
            return response()->json($validation->getMessageBag()->all());
        }

        $data = $request->all();

        // check hash
        if(!isset($data['order_hash'])){
            //Create new row in order table
            $order = Order::create([
                'hash'       => sha1(time().rand()),
                'company_id' => \Request::get('company')->id,
            ]);
        }else{
            $order = Order::where('hash', $data['order_hash'])->get();
            if(!count($order)){
                return response()->json(['error' => 'Invalid order_hash']);
            }
            $order = $order[0];
        }

        if (isset($data['customer_hash'])) {
            $customer = Customer::whereHash($data['customer_hash'])->first();
            if ($customer) {
                $order->update(['customer_id' => $customer->id]);
                $paidMonthlyInvoice = isset($data['paid_monthly_invoice'])? $data['paid_monthly_invoice'] : null;
            }
        }

        // check active_group_id
        if(!$order->active_group_id){
            $order_group = OrderGroup::create([
                'order_id' => $order->id
            ]);

            // update order.active_group_id
            $order->update([
                'active_group_id' => $order_group->id,
            ]);
        }else{
            $order_group = OrderGroup::find($order->active_group_id);
        }

        $this->insertOrderGroup($data, $order, $order_group);

        if(isset($paidMonthlyInvoice) && $paidMonthlyInvoice == "1" && isset($data['plan_id'])){
            $monthly_order_group = OrderGroup::create([
                'order_id' => $order->id
            ]);
            $this->insertOrderGroup($data, $order, $monthly_order_group, 1);
        }

        return $this->respond(['id' => $order->id, 'order_hash' => $order->hash]);
    }

	/**
	 * @param     $data
	 * @param     $order
	 * @param     $order_group
	 * @param int $paidMonthlyInvoice
	 */
	private function insertOrderGroup($data, $order, $order_group, $paidMonthlyInvoice = 0)
    {
        $og_params = [];
        if(isset($data['device_id']) && $paidMonthlyInvoice == 0){
            $og_params['device_id'] = $data['device_id'];
        }
        if(isset($data['plan_id'])){
            $og_params['plan_id'] = $data['plan_id'];

            if ($order->customer && $order->compare_dates && $paidMonthlyInvoice == 0) {
                $og_params['plan_prorated_amt'] = $order->planProRate($data['plan_id']);
            }
            // delete all rows in order_group_addon table associated with this order

            $_oga = OrderGroupAddon::where('order_group_id', $order_group->id)
            ->get();

            $planToAddon = PlanToAddon::wherePlanId($data['plan_id'])->get();

            $addon_ids = [];

            foreach ($planToAddon as $addon) {
                array_push($addon_ids, $addon->addon_id);
            }

            foreach($_oga as $__oga){
                if (!in_array($__oga->addon_id, $addon_ids)) {
                    $__oga->delete();
                }
            }
        }

        if($paidMonthlyInvoice == 0){
            if(isset($data['sim_id'])){
                $sim_id = $data['sim_id'];
                if($sim_id == 0){
                    $sim_id = null;
                }
                $og_params['sim_id'] = $sim_id;
            }

            if(isset($data['sim_num'])){
                $og_params['sim_num'] = $data['sim_num'];
            }

            // if(isset($data['subscription_id'])){
            //     $og_params['subscription_id'] = $data['subscription_id'];
            // }

            if(isset($data['sim_type'])){
                $og_params['sim_type'] = $data['sim_type'];
            }

            if(isset($data['porting_number'])){
                $og_params['porting_number'] = $data['porting_number'];
            }

            if(isset($data['area_code'])){
                $og_params['area_code'] = $data['area_code'];
            }

            if(isset($data['operating_system'])){
                $og_params['operating_system'] = $data['operating_system'];
            }

            if(isset($data['imei_number'])){
                $og_params['imei_number'] = $data['imei_number'];
            }

            if (isset($data['require_device'])) {
                $og_params['require_device'] = $data['require_device'];
            }
        }

        $order_group->update($og_params);

        if(isset($data['addon_id'][0])){
            foreach ($data['addon_id'] as $key => $addon) {
                $ogData = [
                    'addon_id'       => $addon,
                    'order_group_id' => $order_group->id
                ];

                if ($order->customer && $order->compare_dates && $paidMonthlyInvoice== 0) {
                    $amt = $order->addonProRate($addon);
                    $oga = OrderGroupAddon::create(array_merge($ogData, ['prorated_amt' => $amt]));
                } else {
                    $oga = OrderGroupAddon::create($ogData);
                }
            }
        }
    }

	/**
	 * Delete the input order_group_id from database.
	 * If it is set as the active group id, then set order.active_group_id=0
	 *
	 * @param Request $request
	 *
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function remove_from_order(Request $request)
    {
        $hash = $request->input('order_hash');
        $order = Order::where('hash', $hash)->get();
        if(!count($order)){
            return $this->respondError('Invalid order_hash', 400);
        }
        $order = $order[0];

        $data = $request->all();
        $order_group_id = $data['order_group_id'];
        if(!isset($order_group_id)){
            return $this->respondError('Invalid order_group_id', 400);
        }

        $og = OrderGroup::find($order_group_id);
        if(!$og){
            return $this->respondError('Invalid order_group_id', 400);
        }
	    /**
	     * check if this order group is associated with given order_hash
	     */
        if($og->order_id != $order->id){
            return $this->respondError('Given order_group_id is not associated with provided order hash', 400);
        }

        if($data['paid_monthly_invoice'] == 1 && $og->plan_id != null){
            $ogIds = OrderGroup::where([
                ['order_id', $og->order_id],
                ['plan_id', $og->plan_id],
            ])->delete();
        }else{
            $og->delete();
        }
        $order->update(['active_group_id' => 0]);
        return $this->respond(['details' => 'Deleted successfully'], 204);
    }

	/**
	 * @param Request $request
	 *
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function updateShipping(Request $request)
    {
        $data = $request->except('_url');
        $validation = Validator::make(
            $data,
            [
                'hash'             => 'required|exists:order,hash',
                'shipping_fname'   => 'string',
                'shipping_lname'   => 'string',
                'shipping_address1'=> 'string',
                'shipping_address2'=> 'string',
                'shipping_city'    => 'string',
                'shipping_state_id'=> 'string',
                'shipping_zip'     => 'numeric',
            ]
        );

        if ($validation->fails()) {
            return response()->json($validation->getMessageBag()->all());
        }

        Order::whereHash($data['hash'])->update($data);

        return $this->respond(['message' => 'sucessfully Updated']);
    }

	/**
	 * @param Request $request
	 *
	 * @return mixed
	 */
	public function get_company(Request $request)
    {
        return \Request::get('company');
    }


	/**
	 * Get Coupon Details
	 */
	public function getCouponDetails()
    {
        $order = Order::where('hash', $this->order_hash)->first();

        $customer = Customer::find($order->customer_id);
        $order_couppons = OrderCoupon::where('order_id', $order->id)->get();
        $this->couponAmount = [];
        if ($order_couppons) {
            foreach ($order_couppons as $coup) {
                if ($coup->coupon) {
                    $coupon = $coup->coupon;
                    $this->couponAmount[] = $this->ifAddedByCustomerFunction($order->id, $coupon);
                }
            }
        }
    }

	/**
	 * @param $simNum
	 *
	 * @return mixed
	 */
	protected function validateIfTheSimIsUsed($simNum)
    {
	    return Subscription::where([
	    	['sim_card_num', $simNum],
	    	['status', '!=', 'closed']
	    ])->exists();
    }

	/**
	 * @param Request $request
	 *
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function createOrderForBulkOrder(Request $request)
	{
		try {

			$validation = $this->validationRequestForBulkOrder($request);
			if($validation !== 'valid') {
				return $validation;
			}
			$data = $request->all();
			$customer = Customer::find($request->get('customer_id'));

			$this->updateCustomerDates($customer);

			DB::transaction(function () use ($request, $data) {
				$planActivation = $request->get('plan_activation') ?: false;
				$customer = Customer::find($request->get('customer_id'));

				$order = null;
				if(!$planActivation){
					/**
					 * Create new row in order table if the order is not for plan activation
					 */
					$order = Order::create( [
						'hash'              => sha1( time() . rand() ),
						'company_id'        => $request->get( 'company' )->id,
						'customer_id'       => $data[ 'customer_id' ],
						'shipping_fname'    => $customer->billing_fname,
						'shipping_lname'    => $customer->billing_lname,
						'shipping_address1' => $customer->billing_address1,
						'shipping_address2' => $customer->billing_address2,
						'shipping_city'     => $customer->billing_city,
						'shipping_state_id' => $customer->billing_state_id,
						'shipping_zip'      => $customer->billing_zip
					] );
				}

				$orderItems = $request->get( 'orders' );

				$outputOrderItems = [];

				foreach ( $orderItems as $orderItem ) {
					$order_group = null;
					$paidMonthlyInvoice = isset( $orderItem[ 'paid_monthly_invoice' ] ) ? $orderItem[ 'paid_monthly_invoice' ] : null;
					if(!$planActivation) {
						// check active_group_id
//						if ( ! $order->active_group_id ) {
							$order_group = OrderGroup::create( [
								'order_id' => $order->id
							] );
							// update order.active_group_id
//							$order->update( [
//								'active_group_id' => $order_group->id,
//							] );
//						} else {
//							$order_group = OrderGroup::find( $order->active_group_id );
//						}
					} else {
						if(isset( $orderItem['plan_id'])) {
							$order_group = OrderGroup::whereHas( 'order', function ( $query ) use ( $request, $data ) {
								$query->where( [
									[ 'company_id', $request->get( 'company' )->id ],
									[ 'customer_id', $data[ 'customer_id' ] ],
								] );
							} )->where( 'plan_id', $orderItem[ 'plan_id' ] )->where( 'sim_num', $orderItem[ 'sim_num' ] )->first();
						}
					}
					if($order_group) {
						$outputOrderItems[] = $this->insertOrderGroupForBulkOrder( $orderItem, $order, $order_group );
						if ( isset( $paidMonthlyInvoice ) && $paidMonthlyInvoice == "1" && isset( $orderItem[ 'plan_id' ] ) ) {
							$monthly_order_group = OrderGroup::create( [
								'order_id' => $order->id
							] );
							$outputOrderItems[] = $this->insertOrderGroupForBulkOrder( $orderItem, $order, $monthly_order_group, 1 );
						}
					}
				}
				if($order) {
					$this->createInvoice($request, $order, $outputOrderItems, $planActivation);
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
	 * @param     $data
	 * @param     $order
	 * @param     $order_group
	 * @param int $paidMonthlyInvoice
	 *
	 * @return mixed
	 */
	private function insertOrderGroupForBulkOrder($data, $order, $order_group, $paidMonthlyInvoice = 0)
	{
		$og_params = [];
		$subscription_id = null;
		if(isset($data['device_id']) && $paidMonthlyInvoice == 0){
			$og_params['device_id'] = $data['device_id'];
		}
		if(isset($data['plan_id'])){

			$og_params['plan_id'] = $data['plan_id'];

			if ($order->customer && $order->compare_dates && $paidMonthlyInvoice == 0) {
				$og_params['plan_prorated_amt'] = $order->planProRate($data['plan_id']);
			}
			// delete all rows in order_group_addon table associated with this order

			$_oga = OrderGroupAddon::where('order_group_id', $order_group->id)
			                       ->get();

			$planToAddon = PlanToAddon::wherePlanId($data['plan_id'])->get();

			$addon_ids = [];

			foreach ($planToAddon as $addon) {
				array_push($addon_ids, $addon->addon_id);
			}

			foreach($_oga as $__oga){
				if (!in_array($__oga->addon_id, $addon_ids)) {
					$__oga->delete();
				}
			}
			if(isset($data['sim_num'])) {
				if ( ! isset( $data[ 'subscription_id' ] ) ) {
					$subscriptionData = $this->generateSubscriptionData( $data, $order );
					$subscription     = Subscription::create( $subscriptionData );
					$subscription_id  = $subscription->id;
				} else {
					$subscription_id = $data[ 'subscription_id' ];
				}

				if ( $subscription_id ) {
					$og_params[ 'subscription_id' ] = $subscription_id;
				}
			}
		}

		if($paidMonthlyInvoice == 0){
			if(isset($data['sim_id'])){
				$sim_id = $data['sim_id'];
				if($sim_id == 0){
					$sim_id = null;
				}
				$og_params['sim_id'] = $sim_id;
			}

			if(isset($data['sim_num'])){
				$og_params['sim_num'] = $data['sim_num'];
			}

			if(isset($data['sim_type'])){
				$og_params['sim_type'] = $data['sim_type'];
			}

			if(isset($data['porting_number'])){
				$og_params['porting_number'] = $data['porting_number'];
			}

			if(isset($data['area_code'])){
				$og_params['area_code'] = $data['area_code'];
			}

			if(isset($data['operating_system'])){
				$og_params['operating_system'] = $data['operating_system'];
			}

			if(isset($data['imei_number'])){
				$og_params['imei_number'] = $data['imei_number'];
			}

			if (isset($data['require_device'])) {
				$og_params['require_device'] = $data['require_device'];
			}
		}


		if(isset($data['addon_id'][0])){
			foreach ($data['addon_id'] as $key => $addon) {
				$ogData = [
					'addon_id'       => $addon,
					'order_group_id' => $order_group->id
				];

				if ($order->customer && $order->compare_dates && $paidMonthlyInvoice== 0) {
					$amt = $order->addonProRate($addon);
					$oga = OrderGroupAddon::create(array_merge($ogData, ['prorated_amt' => $amt]));
				} else {
					$oga = OrderGroupAddon::create($ogData);
				}
			}
		}
		return tap(OrderGroup::findOrFail($order_group->id))->update($og_params);
	}

	/**
	 * Returns data as array which is to be inserted in subscription table
	 *
	 * @param  $data
	 * @param  Order  $order
	 * @return array
	 */
	protected function generateSubscriptionData($data, $order)
	{
		$plan  = Plan::find($data['plan_id']);

		if ((!isset($data['sim_type']) || $data['sim_type'] == null) && isset($data['sim_id'])) {
			$sim = Sim::find($data['sim_id']);
			$data['sim_type'] = ($sim) ? $sim->name : null;
		}
		if(!isset($data['subscription_status'])){
			$subscriptionStatus = isset($data['sim_id']) || isset($data['device_id']) ? 'shipping' : 'for-activation';
		} else {
			$subscriptionStatus = $data['subscription_status'];
		}

		$output = [
			'order_id'                         =>  $order->id,
			'customer_id'                      =>  $order->customer->id,
			'company_id'                       =>  $order->customer->company_id,
			'order_num'                        =>  $order->order_num,
			'plan_id'                          =>  $data['plan_id'],
			'status'                           =>  $plan && $plan->type === 4 ? 'active' : $subscriptionStatus,
			'sim_id'                           =>  $data['sim_id'] ?? null,
			'sim_name'                         =>  $data['sim_type'] ?? '',
			'sim_card_num'                     =>  $data['sim_num'],
			'device_id'                        =>  $data['device_id'] ?? null,
			'device_os'                        =>  $data['operating_system'] ?? '',
			'device_imei'                      =>  $data['imei_number'] ?? '',
			'subsequent_porting'               =>  ($plan) ? $plan->subsequent_porting : 0,
			'requested_area_code'              =>  $data['area_code'] ?? '',
		];

		if($plan && $plan->type === 4){
			$output['activation_date']  = Carbon::now();
		}

		return $output;
	}

	/**
	 * @param Request $request
	 *
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function listCustomerSimOrder( Request $request )
	{
		try {
			$requestCompany = $request->get('company');
			$validation = Validator::make(
				$request->all(),
				[
					'customer_id'                   => [
						'required',
						'numeric',
						Rule::exists('customer', 'id')->where(function ($query) use ($requestCompany) {
							return $query->where('company_id', $requestCompany->id);
						})
					]
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

			$customerId = $request->input('customer_id');
			$orders = Order::where('customer_id', $customerId)
			              ->where('company_id', $requestCompany->id)
			              ->get();
			if(!$orders){
				return $this->respond([
					'status'    => 'success',
					'data'      => 'No orders available'
				]);
			}
			$ordersOutput = [];
			foreach($orders as $order){
				$hash = $order->hash;

				$orderGroups = $tempOrderGroups = $tempOrder = [];

				if($hash) {
					$order_groups = OrderGroup::with( [
						'order',
						'sim',
						'device',
						'device.device_image'
					] )->whereHas( 'order', function ( $query ) use ( $hash ) {
						$query->where( 'hash', $hash );
					} )->get();

					foreach ( $order_groups as $key => $og ) {
						if ( empty( $tempOrder ) ) {
							$tempOrder = [
								"id"                     => $og->order->id,
								"order_hash"             => $og->order->hash,
								"active_group_id"        => $og->order->active_group_id,
								"active_subscription_id" => $og->order->active_subscription_id,
								"order_num"              => $og->order->order_num,
								"status"                 => $og->order->status,
								"customer_id"            => $og->order->customer_id,
								'order_groups'           => [],
								'operating_system'       => $og->operating_system,
								'imei_number'            => $og->imei_number
							];
						}

						$tempOrderGroups = [
							'id'                => $og->id,
							'sim'               => $og->sim,
							'sim_num'           => $og->sim_num,
							'sim_type'          => $og->sim_type,
							'plan'              => $og->plan,
							'porting_number'    => $og->porting_number,
							'area_code'         => $og->area_code,
							'device'            => $og->device_detail,
							'operating_system'  => $og->operating_system,
							'imei_number'       => $og->imei_number,
							'plan_prorated_amt' => $og->plan_prorated_amt,
							'subscription'      => $og->subscription,
						];

						array_push( $orderGroups, $tempOrderGroups );
					}
					$tempOrder[ 'order_groups' ] = $orderGroups;
				}
				array_push($ordersOutput, $tempOrder);
			}

			return $this->respond([
				'status'    => 'success',
				'data'      => $ordersOutput
			]);
		} catch(\Exception $e) {
			Log::info( $e->getMessage(), 'List customer sim order' );
			$response = [
				'status' => 'error',
				'data'   => $e->getMessage()
			];
			return $this->respond( $response, 503 );
		}
	}

	/**
	 * Preview Order for Bulk Order
	 * @param Request $request
	 *
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function previewOrderForBulkOrder(Request $request)
	{
		try {
			$output = [];
			$this->validationRequestForBulkOrder($request);

			$data = $request->all();

			$orderItems = $request->get( 'orders' );

			$output['totalPrice'] =  $this->totalPriceForPreview($orderItems);
//			$output['subtotalPrice'] = $this->subTotalPriceForPreview($orderItems);
//			$output['monthlyCharge'] = $this->calMonthlyChargeForPreview($orderItems);
//			$output['taxes'] = $this->calTaxesForPreview($orderItems);
			$output['regulatory'] = $this->calRegulatoryForPreview($orderItems);
			$output['shippingFee'] = $this->getShippingFeeForPreview($orderItems);

			$successResponse = [
				'status'    => 'success',
				'data'      => $output
			];
			return $this->respond($successResponse);

		} catch(\Exception $e) {
			Log::info( $e->getMessage(), 'Preview Order for Bulk Order' );
			$response = [
				'status' => 'error',
				'data'   => $e->getMessage()
			];
			return $this->respond( $response, 503 );
		}
	}

	/**
	 * @param $request
	 *
	 * @return bool|\Illuminate\Http\JsonResponse
	 */
	private function validationRequestForBulkOrder($request)
	{
		$requestCompany = $request->get('company');
		if ( !$requestCompany->enable_bulk_order ) {
			$notAllowedResponse = [
				'status' => 'error',
				'data'   => 'Bulk Order not enabled for the requesting company'
			];
			return $this->respond($notAllowedResponse, 503);
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
					'numeric',
					Rule::exists('plan', 'id')->where(function ($query) use ($requestCompany) {
						return $query->where('company_id', $requestCompany->id);
					})
				],
				'orders.*.sim_id'               =>  [
					'numeric',
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
					'numeric',
					'required_with:orders.*.sim_id',
					Rule::unique('subscription', 'sim_card_num')->where(function ($query)  {
						return $query->where('status', '!=', 'closed');
					})
				],
				'orders.*.sim_type'             => 'string',
				'orders.*.porting_number'       => 'string',
				'orders.*.area_code'            => 'string',
				'orders.*.operating_system'     => 'string',
				'orders.*.imei_number'          => 'digits_between:14,16',
				'orders.*.subscription_status'  => 'string',
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
