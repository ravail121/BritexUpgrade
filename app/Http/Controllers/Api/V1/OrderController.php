<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\BaseController;

use App\Model\OrderCoupon;
use App\Model\Tax;
use Validator;
use Carbon\Carbon;
use App\Model\Order;
use App\Model\Company;
use App\Model\Invoice;
use App\Model\Customer;
use App\Model\OrderGroup;
use App\Model\PlanToAddon;
use Illuminate\Http\Request;
use App\Model\OrderGroupAddon;
use App\Model\BusinessVerification;

/**
 * Class OrderController
 *
 * @package App\Http\Controllers\Api\V1
 */
class OrderController extends BaseController
{
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
    protected $tax_id;
    protected $tax_total;
    protected $total_price;
    protected $subtotalPriceAmount;
    protected $couponAmount;
    protected $taxrate;
    protected $order_hash;

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
	public function get(Request $request){

        $hash = $request->input('order_hash');
        $this->order_hash = $hash;
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
	                        'goknows_api_key'
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
//        $order['totalPrice'] =  $this->totalPrice();
//        $order['subtotalPrice'] =  $this->subTotalPrice();
//        $order['activeGroupId'] =  $this->getActiveGroupId();
//        $order['monthlyCharge'] =  $this->calMonthlyCharge();
//        $order['taxes'] =  $this->calTaxes();
//        $order['regulatory'] =  $this->calRegulatory();
//        $order['shippingFee'] =  $this->getShippingFee();
//        $order['coupons'] =  $this->coupon();
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
	 * @param Request $request
	 *
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function remove_from_order(Request $request)
    {
        /*
        Delete the input order_group_id from database.  If it is set as the active group id, then set order.active_group_id=0
        */

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
        //check if this ordergroup is associated with given order_hash
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
        // $apiKey = $request->Authorization;
        // $businessVerification = Company::where('api_key',$apiKey)->first()->business_verification;
        return \Request::get('company');
    }


//    public function destroy($id){
//         Order::find($id)->delete();
//         //$orders = OrderController::find($id);
//         return redirect()->back()->withErrors('Successfully deleted!');
//     }
// }



/**-----------Starting-------------**/
/**-------------------------------**/

/**
 * Calculates the Total Price of Cart
 *
 * @return float  $totalPrice
 */
public function totalPrice()
{
    if($this->total_price){
        $this->total_price = 0;
    }
    $this->calDevicePrices();
    $this->getPlanPrices();
    $this->getSimPrices();
    $this->getAddonPrices();
    $this->calTaxes();
    $this->getShippingFee();
    $this->calRegulatory();
    $this->coupon();
    $price[] = ($this->prices) ? array_sum($this->prices) : 0;
    $price[] = ($this->regulatory) ? array_sum($this->regulatory) : 0;
    $price[] = ($this->couponAmount);
    if($this->tax_total===0){
        $price[] = ($this->taxes) ? number_format(array_sum($this->taxes), 2) : 0;
    } else {
        $price[] = number_format($this->tax_total, 2);
    }
    $price[] = ($this->activation) ? array_sum($this->activation) : 0;
    $price[] = ($this->shippingFee) ? array_sum($this->shippingFee) : 0;
    $totalPrice = array_sum($price);
    $this->total_price = $totalPrice;
    return $totalPrice;
}


protected function calDevicePrices()
{
    $this->prices = [];
    $activeGroupId = $this->getActiveGroupId();
    if ($this->cartItems != null) {
        if (count($this->cartItems['order_groups'])) {
            foreach ($this->cartItems['order_groups'] as $cart) {
                if ($cart['device'] != null) {
                    if ($cart['plan'] == null) {
                        if ($cart['id'] == $activeGroupId) {
                            $this->prices[] = $cart['device']['amount_w_plan'];
                        } else {
                            $this->prices[] = $cart['device']['amount'];
                        }
                    } else {
                        $this->prices[] = $cart['device']['amount_w_plan'];
                    }
                }
            }
        }
    }
    return true;
}


public function getActiveGroupId()
{
    return (isset($this->cartItems['active_group_id'])) ? $this->cartItems['active_group_id'] : null;
}

protected function getPlanPrices()
{
    $this->activation = [];
    if ($this->cartItems != null) {
        if (count($this->cartItems['order_groups'])) {
            foreach ($this->cartItems['order_groups'] as $cart) {
                if ($cart['plan']['amount_onetime'] > 0) {
                    $this->activation[] = $cart['plan']['amount_onetime'];
                }
                if ($cart['plan_prorated_amt']) {
                    $this->prices[] = $cart['plan_prorated_amt'];
                } else {
                    $this->prices[] = ($cart['plan'] != null) ? $cart['plan']['amount_recurring'] : [];
                }
            }
        }
    }
    return true;
}

/**
 * It returns the array of Sim-prices from an array
 *
 * @param   array  $type
 * @return  array
 */
protected function getSimPrices()
{
    if ($this->cartItems != null) {
        if (count($this->cartItems['order_groups'])) {
            foreach ($this->cartItems['order_groups'] as $cart) {
                if ($cart['sim'] != null && $cart['plan'] != null) {
                    $this->prices[] = $cart['sim']['amount_w_plan'];
                } elseif ($cart['sim'] != null && $cart['plan'] == null) {
                    $this->prices[] = $cart['sim']['amount_alone'];
                }
            }
        }
    }
    return true;
}

/**
 * It returns the array of Addon-prices from an array
 *
 * @param   array  $type
 * @return  array
 */
protected function getAddonPrices()
{
    if ($this->cartItems != null) {
        if (count($this->cartItems['order_groups'])) {
            foreach ($this->cartItems['order_groups'] as $cart) {
                if ($cart['addons'] != null) {
                    foreach ($cart['addons'] as $addon) {
                        if ($addon['prorated_amt'] != null) {
                            $this->prices[] = $addon['prorated_amt'];
                        } else {
                            $this->prices[] = $addon['amount_recurring'];
                        }
                    }
                }
            }
        }
    }
    return true;
}


public function calTaxes($taxId = null)
{
    $this->taxes = [];
    if ($this->cartItems != null) {
        if (count($this->cartItems['order_groups'])) {
            foreach ($this->cartItems['order_groups'] as $cart) {
                $this->taxes[] = number_format($this->calTaxableItems($cart, $taxId), 2);
            }
        }
    }
    $taxes = ($this->taxes) ? array_sum($this->taxes) : 0;
    $taxId ?  $this->tax_total = $taxes : $this->tax_total = 0;
    $taxId ? $this->totalPrice() : null; // to add tax to total without refresh
    return $taxes;
}



/**
 * Calculates the Sub-Total Price of Cart
 *
 * @return float  $subtotalPrice
 */
public function subTotalPrice()
{
    $this->calDevicePrices();
    $this->getPlanPrices();
    $this->getSimPrices();
    $this->getAddonPrices();
    $price[] = ($this->prices) ? array_sum($this->prices) : 0;
    $price[] = ($this->activation) ? array_sum($this->activation) : 0;
    $this->subTotalPriceAmount = array_sum($price);
    return $this->subTotalPriceAmount;
}

/**
 * Calculates the monthly charge of Cart (plans + addons)
 *
 * @return float  $monthlyCharge
 */
public function calMonthlyCharge()
{
    $this->prices = [];
    $this->getOriginalPlanPrice();
    $this->getOriginalAddonPrice();
    $price = ($this->prices) ? array_sum($this->prices) : 0;
    if(isset(session('cart')['paid_invoice'])){
        $price /= 2;
    }
    return $price;
}


public function calRegulatory()
{
    $this->regulatory = [];
    if ($this->cartItems != null) {
        if (count($this->cartItems['order_groups'])) {
            foreach ($this->cartItems['order_groups'] as $cart) {
                if($cart['plan'] != null && !isset($cart['status'])){
                    if ($cart['plan']['regulatory_fee_type'] == 1) {
                        $this->regulatory[] = $cart['plan']['regulatory_fee_amount'];
                    }elseif ($cart['plan']['regulatory_fee_type'] == 2) {
                        if($cart['plan_prorated_amt'] != null){
                            $this->regulatory[] = number_format($cart['plan']['regulatory_fee_amount']*$cart['plan_prorated_amt']/100, 2);
                        }else{
                            $this->regulatory[] = number_format($cart['plan']['regulatory_fee_amount']*$cart['plan']['amount_recurring']/100, 2);
                        }
                    }
                }
            }
        }
    }
    $regulatory = ($this->regulatory) ? array_sum($this->regulatory) : 0;
    return $regulatory;
}


public function coupon()
{
    $order = Order::where('hash', $this->order_hash)->first();

    $total = 0;
    $order_couppons = OrderCoupon::where('order_id', $order->id)->get();
    foreach ($order_couppons as $order_couppon){
        $this->couponAmount[] = $order_couppon->coupon;
        $total += $order_couppon->coupon->amount;
    }
    return $total;
}

/**
 * Gets Shipping fee
 *
 * @return float  $shippingFee
 */
public function getShippingFee()
{
    $this->shippingFee = [];
    if ($this->cartItems != null) {
        if (count($this->cartItems['order_groups'])) {
            foreach ($this->cartItems['order_groups'] as $cart) {
                if ($cart['device'] != null) {
                    if ($cart['device']['shipping_fee'] != null) {
                        $this->shippingFee[] = $cart['device']['shipping_fee'];
                    }
                } if ($cart['sim'] != null) {
                    if ($cart['sim']['shipping_fee'] != null) {
                        $this->shippingFee[] = $cart['sim']['shipping_fee'];
                    }
                }
            }
        }
    }
    $shippingFee = ($this->shippingFee) ? array_sum($this->shippingFee) : 0;
    return $shippingFee;
}

public function calTaxableItems($cart, $taxId)
{
    $_tax_id = null;
    $stateId = '';
    if (!$taxId) {
        if ($this->cartItems['business_verification'] && isset($this->cartItems['business_verification']['billing_state_id'])) {
            $_tax_id = $this->cartItems['business_verification']['billing_state_id'];
        } elseif (session('cart')['customer'] && isset(session('cart')['customer']['billing_state_id'])) {
            $_tax_id = $this->cartItems['customer']['billing_state_id'];
        }
    } else {
        session(['tax_id' => $taxId]);
    }
    $stateId = ['tax_id' => $_tax_id];
    $taxRate    = $this->taxrate($stateId);
    if (!$_tax_id || $_tax_id && isset($taxRate['tax_rate']) && $_tax_id != $taxRate['tax_rate']) {
        $taxRate    = $this->taxrate($stateId);
        $this->taxrate = isset($taxRate['tax_rate']) ? $taxRate['tax_rate'] : 0;
    }
    $taxPercentage  = $_tax_id / 100;
    if(isset($cart['status']) && $cart['status'] == "SamePlan"){
        //Addon
        $addons = $this->addTaxesToAddons($cart, $taxPercentage);
        return $addons;
    }
    if(isset($cart['status']) && $cart['status'] == "Upgrade"){
        //Plans
        $plans =$this->addTaxesToPlans($cart, $cart['plan'], $taxPercentage);
        //Addons
        $addons = $this->addTaxesToAddons($cart, $taxPercentage);
        return $plans + $addons;
    }
    //Devices
    $devices        = $this->addTaxesDevices($cart, $cart['device'], $taxPercentage);
    //Sims
    $sims           = $this->addTaxesSims($cart, $cart['sim'], $taxPercentage);
    //Plans
    $plans          = $this->addTaxesToPlans($cart, $cart['plan'], $taxPercentage);
    //Addons
    $addons         = $this->addTaxesToAddons($cart, $taxPercentage);
    return $devices + $sims + $plans + $addons;
}

public function taxrate($stateId)
{
    $company = \Request::get('company')->load('carrier');
    if (array_key_exists('tax_id', $stateId)) {
        $rate = Tax::where('state', $stateId['tax_id'])
            ->where('company_id', $company->id)
            ->pluck('rate')
            ->first();
        return ['tax_rate' => $rate];
    }
    $msg = $this->respond(['error' => 'Hash is required']);
    if (array_key_exists('hash', $stateId)) {
        $customer = Customer::where(['hash' => $stateId['hash']])->first();
        if ($customer) {
            if (array_key_exists("paid_monthly_invoice", $stateId)) {
                $date = Carbon::today()->addDays(6)->endOfDay();
                $invoice = Invoice::where([
                    ['customer_id', $customer->id],
                    ['status', Invoice::INVOICESTATUS['closed&paid']],
                    ['type', Invoice::TYPES['monthly']]
                ])->whereBetween('start_date', [Carbon::today()->startOfDay(), $date])->where('start_date', '!=', Carbon::today())->first();

                $customer['paid_monthly_invoice'] = $invoice ? 1 : 0;
            }
            $customer['company'] = $company;
            $msg = $this->respond($customer);
        } else {
            $msg = $this->respond(['error' => 'customer not found']);

        }
    }
    return $msg;
}

public function addTaxesToPlans($cart, $item, $taxPercentage)
{
    $planTax = [];
    if ($item != null && $item['taxable']) {
        $amount = $cart['plan_prorated_amt'] != null ? $cart['plan_prorated_amt'] : $item['amount_recurring'];
        $amount = $item['amount_onetime'] != null ? $amount + $item['amount_onetime'] : $amount;
        if ($this->couponAmount) {
            $discounted = $this->getCouponPrice($this->couponAmount, $item, 1);
            $amount = $discounted > 0 ? $amount - $discounted : $amount;
        }
        $planTax[] = $taxPercentage * $amount;
    }
    return !empty($planTax) ? array_sum($planTax) : 0;
}

protected function getCouponPrice($couponData, $item, $itemType)
{
    $productDiscount = 0;
    foreach($couponData as $coupon) {
        $type = $coupon[ 'coupon_type' ];
        if ( $type == 1 ) { // Applied to all
            $appliedTo = $coupon[ 'applied_to' ][ 'applied_to_all' ];
        } elseif ( $type == 2 ) { // Applied to types
            $appliedTo = $coupon[ 'applied_to' ][ 'applied_to_types' ];
        } elseif ( $type == 3 ) { // Applied to products
            $appliedTo = $coupon[ 'applied_to' ][ 'applied_to_products' ];
        }
        if ( count( $appliedTo ) ) {
            foreach ( $appliedTo as $product ) {
                if ( $product[ 'order_product_type' ] == $itemType && $product[ 'order_product_id' ] == $item[ 'id' ] ) {
                    $productDiscount += $product[ 'discount' ];
                }
            }
        }

    }
    return $productDiscount;
}

public function addTaxesDevices($cart, $item, $taxPercentage)
{
    $itemTax = [];
    if ($item && $item['taxable']) {
        $amount = $cart['plan'] != null ? $item['amount_w_plan'] : $item['amount'];

        if ($this->couponAmount ) {
            $discounted = $this->getCouponPrice($this->couponAmount, $item, 2);
            $amount = $discounted > 0 ? $amount - $discounted : $amount;
        }
        $itemTax[] = $taxPercentage * $amount;
    }
    return !empty($itemTax) ? array_sum($itemTax) : 0;
}

public function addTaxesSims($cart, $item, $taxPercentage)
{
    $itemTax = [];
    if ($item && $item['taxable']) {
        $amount = $cart['plan'] != null ? $item['amount_w_plan'] : $item['amount_alone'];
        if ($this->couponAmount) {
            $discounted = $this->getCouponPrice($this->couponAmount, $item, 3);
            $amount = $discounted > 0 ? $amount - $discounted : $amount;
        }
        $itemTax[] = $taxPercentage * $amount;
    }
    return !empty($itemTax) ? array_sum($itemTax) : 0;
}

public function addTaxesToAddons($cart, $taxPercentage)
{
    $addonTax = [];
    if ($cart['addons'] != null) {
        foreach ($cart['addons'] as $addon) {
            if ($addon['taxable'] == 1) {
                $amount = $addon['prorated_amt'] != null ? $addon['prorated_amt'] : $addon['amount_recurring'];
                if ($this->couponAmount) {
                    $discounted = $this->getCouponPrice($this->couponAmount, $addon, 4);
                    $amount = $discounted > 0 ? $amount - $discounted : $amount;
                }
                $addonTax[] = $taxPercentage * $amount;
            }
        }
    }
    return !empty($addonTax) ? array_sum($addonTax) : 0;
}


/**
 * It returns the array of Plan-prices from an array
 *
 * @param   array  $type
 * @return  array
 */
protected function getOriginalPlanPrice()
{
    if ($this->cartItems != null) {
        if (count($this->cartItems['order_groups'])) {
            foreach ($this->cartItems['order_groups'] as $cart) {
                $this->prices[] = ($cart['plan'] != null) ? $cart['plan']['amount_recurring'] : [];
            }
        }
    }
    return true;
}

/**
 * It returns the array of Addon-prices from an array
 *
 * @param   array  $type
 * @return  array
 */
protected function getOriginalAddonPrice()
{
    if ($this->cartItems != null) {
        if (count($this->cartItems['order_groups'])) {
            foreach ($this->cartItems['order_groups'] as $cart) {
                if ($cart['addons'] != null) {
                    foreach ($cart['addons'] as $addon) {
                        if($addon['subscription_addon_id']!= null){
                            $this->prices[] = [];
                        }else{
                            $this->prices[] = $addon['amount_recurring'];
                        }
                    }
                }
            }
        }
    }
    return true;
}

/**-------------------------------**/
/**-------------------------------**/
}
