<?php

namespace App\Http\Controllers\Api\V1;


use Exception;
use Carbon\Carbon;
use App\Model\Plan;
use App\Model\Order;
use App\Model\Coupon;
use App\Model\Customer;
use App\Model\OrderCoupon;
use App\Model\Subscription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\V1\Traits\InvoiceCouponTrait;

/**
 * Class CouponController
 *
 * @package App\Http\Controllers\Api\V1
 */
class CouponController extends Controller
{
    use InvoiceCouponTrait;

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
	 * @var
	 */
	protected $failedResponse;

	/**
	 * @var int[]
	 */
	protected $totalTaxableAmount = [0];

	/**
	 * @param Request $request
	 *
	 * @return array|array[]|string[]|null
	 */
	public function addCoupon(Request $request)
    {
        try {
            // Request from cart plans
            if ($request->for_plans) {
                $codes = [];
                foreach ($request->data_for_plans as $data) {
                    if ($data['coupon_id']) {
                        $coupon = Coupon::find($data['coupon_id']);
                        $codes[] = [
                            'coupon' => [
                                'info'      => $this->checkEligibleProducts($coupon),
                                'code'      => $coupon->code
                            ],
                            'order_group_id'    => $data['order_group_id'],
                            'plan'              => Plan::find($data['plan_id'])->name
                        ];
                    }
                }
                return ['coupon_data' => $codes];
            }

            // Request from cart tooltip
            $coupon = Coupon::where('code', $request->code)->first();
            if ($request->only_details) {
                return [
                	'coupon_amount_details' => $this->checkEligibleProducts($coupon)
                ];
            }

	        $order_id = $request->input('order_id');
            // Regulator textbox request
            if (!$this->couponIsValid($coupon)) {
	            return [
	            	'error'     => $this->failedResponse
	            ];
            }

	        /**
	         * Check if the coupon are stackable
	         */
            if(!$this->couponAreStackableAndUnused($coupon, $order_id)){
	            return [
	            	'error'     => $this->failedResponse
	            ];
            }

            if ($request->subscription_id) {
                return $this->ifAddedFromAdmin($request, $coupon);
            } else {
                return $this->ifAddedByCustomer($request, $coupon);
            }

        } catch (Exception $e) {
            \Log::info($e->getMessage() . ' on line number: '.$e->getLine() . ' in CouponController');
            return [
            	'total' => 0,
	            'error' => 'Server error'
            ];
        }    
    }

	/**
	 * @param $coupon
	 *
	 * @return bool
	 */
	protected function couponIsValid($coupon)
    {
        if (!$coupon || $coupon['company_id'] != \Request::get('company')->id) {
            $this->failedResponse = 'Coupon is invalid';
            return false;
        }
        if ($coupon['active']) {
            if ($coupon['multiline_restrict_plans'] && !count($coupon->multilinePlanTypes)) {
                $this->failedResponse = 'Multiline plan data missing';
                return false;
            }
            if ($coupon['class'] == Coupon::CLASSES['APPLIES_TO_SPECIFIC_TYPES'] && !count($coupon->couponProductTypes)) {
                $this->failedResponse = 'Coupon product types data missing';
                return false;
            }
            if ($coupon['class'] == Coupon::CLASSES['APPLIES_TO_SPECIFIC_PRODUCT'] && !count($coupon->couponProducts)) {
                $this->failedResponse = 'Coupon products data missing';
                return false;
            }
            return true;
        } else {
            $this->failedResponse = 'Not active';
            return false;
        }
    }

	/**
	 * @param       $order
	 * @param       $customer
	 * @param       $coupon
	 * @param false $admin
	 *
	 * @return bool
	 */
	protected function isApplicable($order, $customer, $coupon, $admin = false)
    {
        if ($admin) {
            $totalSubscriptions = $customer->billableSubscriptionsForCoupons->count();
        } else {
            $accountSubscriptions   = $customer->billableSubscriptionsForCoupons;
            $cartSubscriptions      = $order->allOrderGroup->where('plan_id', '!=', null);
            $totalSubscriptions     = $accountSubscriptions->count() + $cartSubscriptions->count();

            // $autoAddCoupon      = Plan::where('auto_add_coupon_id', $coupon->id);        
            // if ($autoAddCoupon->count()) {
            //     $eligibleOrder = $this->ifAutoAdd($autoAddCoupon, $order->allOrderGroup);
            //     if (!$eligibleOrder) {
            //         $this->failedResponse = 'Coupon not available';
            //         return false;
            //     }
            // }
            $multilineRestrict  = $coupon->multiline_restrict_plans ? $coupon->multilinePlanTypes->pluck('plan_type')->toArray() : null;
            if ($multilineRestrict) {
                $totalIds = array_merge($accountSubscriptions->pluck('plan_id')->toArray(), $cartSubscriptions->pluck('plan_id')->toArray());
                $eligibleSubs = array_filter($cartSubscriptions->pluck('plan_id')->toArray(), function ($id) use ($multilineRestrict) {
                    $plan = Plan::find($id);
                    if (in_array($plan->type, $multilineRestrict)) {
                        return $plan->id;
                    }
                });
                if (!count($eligibleSubs)) {
                    $this->failedResponse = 'Requirements not met';
                    return false;
                }
            }
        }
        if ($coupon['multiline_min'] && $totalSubscriptions < $coupon['multiline_min']) {
            $this->failedResponse = 'Min subscriptions required: '.$coupon['multiline_min'];
            return false;
        } 
        if ($coupon['multiline_max'] && $totalSubscriptions > $coupon['multiline_max']) {
            $this->failedResponse = 'Max subscriptions required: '.$coupon['multiline_max'];
            return false;
        }
        return $this->couponCanBeUsed($coupon);
    }

	/**
	 * @param $coupon
	 *
	 * @return bool
	 */
	protected function couponCanBeUsed($coupon)
    {
        $today              = Carbon::now();
        $couponStartDate    = $coupon->start_date ? Carbon::parse($coupon->start_date) : null;
        $couponExpiryDate   = $coupon->end_date ? Carbon::parse($coupon->end_date) : null;
        if ($couponStartDate && $today < $couponStartDate) {
            $this->failedResponse = 'Starts: '.$couponStartDate;
            return false;
        } elseif ($couponExpiryDate && ($today >= $couponExpiryDate)) {
            $this->failedResponse = 'Expired: '.$couponExpiryDate;
            return false;
        } elseif ($coupon['num_uses'] >= $coupon['max_uses']) {
            $this->failedResponse = 'Not available anymore';
            return false;
        } 
        return true;
    }

	/**
	 * @param $request
	 * @param $coupon
	 *
	 * @return array|string[]|null
	 */
	public function ifAddedFromAdmin($request, $coupon)
    {
        $subscription = Subscription::find($request->subscription_id);
        $customer = $subscription->customerRelation;
        if ($subscription->subscriptionCoupon->where('coupon_id', $coupon->id)->count()) {
            $this->failedResponse = 'Coupon already used for this subscription';
            return ['error' => $this->failedResponse];
        }
        if (!$this->isApplicable(false, $customer, $coupon, true)) {
            return ['error' => $this->failedResponse];
        }
        $insert = $this->insertIntoTables($coupon, $customer->id, [$subscription->id], true);
        return $insert;
    }

	/**
	 * @param $request
	 * @param $coupon
	 *
	 * @return array
	 */
	public function ifAddedByCustomer($request, $coupon)
    {
        $order  = Order::find($request->order_id);
        $customer = Customer::find($request->customer_id);
        $couponEligibleFor = $this->checkEligibleProducts($coupon);

        $stateTax = isset($customer->stateTax->rate) ? $customer->stateTax->rate : 0;
        $appliedToAll       = $coupon['class'] == Coupon::CLASSES['APPLIES_TO_ALL']              ?  $this->appliedToAll($coupon, $order, $stateTax) : 0;
        $appliedToTypes     = $coupon['class'] == Coupon::CLASSES['APPLIES_TO_SPECIFIC_TYPES']   ?  $this->appliedToTypes($coupon, $order, $stateTax) : 0;
        $appliedToProducts  = $coupon['class'] == Coupon::CLASSES['APPLIES_TO_SPECIFIC_PRODUCT'] ?  $this->appliedToProducts($coupon, $order, $stateTax) : 0;                
        
        if ($this->isApplicable($order, $customer, $coupon)) {
	        OrderCoupon::updateOrCreate([
		        'order_id'      => $order->id,
		        'coupon_id'     => $coupon->id
	        ]);
            $total = $appliedToAll['total'] + $appliedToTypes['total'] + $appliedToProducts['total'];

            return [
                'total'                 => $total,
                'code'                  => $coupon->code,
                'coupon_type'           => $coupon->class,
                'percentage'            => $coupon->fixed_or_perc == self::FIXED_PERC_TYPES['percentage'],
                'applied_to'            => [
                    'applied_to_all'        => $appliedToAll['applied_to'],
                    'applied_to_types'      => $appliedToTypes['applied_to'],
                    'applied_to_products'   => $appliedToProducts['applied_to'],
                ],
                'coupon_amount_details' => $couponEligibleFor,
                'coupon_tax'            => array_sum($this->totalTaxableAmount) * $stateTax / 100,
	            'is_stackable'          => $coupon->stackable
            ];
        } else {
            return [
            	'error'     =>  $this->failedResponse
            ];
        }
    }

	/**
	 * @param $coupon
	 * @param $order
	 * @param $tax
	 *
	 * @return array
	 */
	protected function appliedToAll($coupon, $order, $tax)
    {
        $isPercentage       = $coupon->fixed_or_perc == self::FIXED_PERC_TYPES['percentage'];
        $multilineRestrict  = $coupon->multiline_restrict_plans ? $coupon->multilinePlanTypes->pluck('plan_type')->toArray() : null;
        $countItems         = 0;
        $totalDiscount      = 0;
        $orderGroups        = $order->allOrderGroup;
	    $orderCouponProduct = [];
        
        foreach ($orderGroups as $og) {
            // plan charges
            if ($og->plan_id) {
                $planData = $this->couponForPlans($og, $isPercentage, $coupon, $tax);
                if ($planData['discount'] == 0 || !$planData['discount']) continue;
                $totalDiscount += $planData['discount'];
                $orderCouponProduct[] = $planData['products'];
                $planData['discount'] ? $countItems++ : null;
            }
            // device charges
            if ($og->device_id) {
                $deviceData = $this->couponForDevice($og, $isPercentage, $coupon, $tax);
                if (!$deviceData['discount'] || $deviceData['discount'] == 0) continue;
                $totalDiscount += $deviceData['discount'];
                $orderCouponProduct[] = $deviceData['products'];
                $deviceData['discount'] ? $countItems++ : null;
            }
            // sim charges
            if ($og->sim_id) {
                $simData = $this->couponForSims($og, $isPercentage, $coupon, $tax);
                if ($simData['discount'] == 0 || !$simData['discount']) continue;
                $totalDiscount += $simData['discount'];
                $orderCouponProduct[] = $simData['products'];
                $simData['discount'] ? $countItems++ : null;
            }
            // addon charges
            if ($og->plan_id) {
                foreach ($og->addons as $addon) {
                    $addonData = $this->couponForAddons($order, $addon, $isPercentage, $coupon, $og, $tax);
                    if ($addonData['discount'] == 0 || !$addonData['discount']) continue;
                    $totalDiscount += $addonData['discount'];
                    $orderCouponProduct[] = $addonData['products'];
                    $addonData['discount'] ? $countItems++ : null;
                }
            }
        }
        $orderCouponProduct ? $this->orderCoupon($orderCouponProduct, $order) : null;
        return ([
            'total'      => str_replace(',', '', number_format($totalDiscount, 2)),
            'applied_to' => isset($orderCouponProduct) ? $orderCouponProduct : [],
            'amount'     => $coupon->amount
        ]);
    }

	/**
	 * @param $couponMain
	 * @param $order
	 * @param $tax
	 *
	 * @return array
	 */
	protected function appliedToTypes($couponMain, $order, $tax)
    {
        $isPercentage       = $couponMain->fixed_or_perc == self::FIXED_PERC_TYPES['percentage'];
        $multilineRestrict  = $couponMain->multiline_restrict_plans ? $couponMain->multilinePlanTypes->pluck('plan_type')->toArray() : null;
        $totalDiscount      = 0;

        foreach ($couponMain->couponProductTypes as $coupon) {
            foreach ($order->allOrderGroup as $og) {
                // For Device types
                if ($coupon->type == self::SPECIFIC_TYPES['DEVICE'] && $og->device_id) {
                    $amountForDevices    = $coupon->amount;
                    $deviceData         = $this->couponForDevice($og, $isPercentage, $coupon, $tax);
                    if (!$deviceData['discount'] || $deviceData['discount'] == 0) continue;
                    $totalDiscount      += $deviceData['discount'];
                    $orderCouponProduct[] = $deviceData['products'];
                }
                // For Plan types
                if ($coupon->type == self::SPECIFIC_TYPES['PLAN'] && $og->plan_id) {
                    $amountForPlans     = $coupon->amount;
                    if ($coupon->sub_type && $og->plan->type != $coupon->sub_type) continue;
                    $planData           = $this->couponForPlans($og, $isPercentage, $coupon, $tax);
                    if ($planData['discount'] == 0 || !$planData['discount']) continue;
                    $totalDiscount += $planData['discount'];
                    $orderCouponProduct[] = $planData['products'];
                }
                // For Sim types
                if ($coupon['type'] == self::SPECIFIC_TYPES['SIM'] && $og->sim_id) {
                    $amountForSims      = $coupon->amount;
                    $simData            = $this->couponForSims($og, $isPercentage, $coupon, $tax);
                    if ($simData['discount'] == 0 || !$simData['discount']) continue;
                    $totalDiscount += $simData['discount'];
                    $orderCouponProduct[] = $simData['products'];
                }
                //For Addon types
                if ($coupon['type'] == self::SPECIFIC_TYPES['ADDON'] && $og->addons->count()) {
                    foreach ($og->addons as $addon) {
                        $amountForAddons   = $coupon->amount;
                        $addonData = $this->couponForAddons($order, $addon, $isPercentage, $coupon, $og, $tax);
                        if ($addonData['discount'] == 0 || !$addonData['discount']) continue;
                        $totalDiscount += $addonData['discount'];
                        $orderCouponProduct[] = $addonData['products'];
                    }
                }
            }
        }
        isset($orderCouponProduct) ? $this->orderCoupon($orderCouponProduct, $order) : null;
        return ([
            'total'         => str_replace(',', '', $totalDiscount),
            'applied_to'    => isset($orderCouponProduct) ? $orderCouponProduct : [],
            'amount'        => [
                'plan'  => isset($amountForPlans) ? $amountForPlans : 0,
                'device'=> isset($amountForDevices) ? $amountForDevices : 0,
                'sims'  => isset($amountForSims) ? $amountForSims : 0,
                'addons'=> isset($amountForAddons) ? $amountForAddons : 0,
            ]
        ]);
    }

	/**
	 * @param $couponMain
	 * @param $order
	 * @param $tax
	 *
	 * @return array
	 */
	protected function appliedToProducts($couponMain, $order, $tax)
    {
        $isPercentage       = $couponMain['fixed_or_perc'] == self::FIXED_PERC_TYPES['percentage'];
        $multilineRestrict  = $couponMain['multiline_restrict_plans'] == 1 ? $couponMain->multilinePlanTypes->pluck('plan_type')->toArray() : null;
        $totalDiscount      = 0;

        foreach ($couponMain->couponProducts as $coupon) {
            foreach ($order->allOrderGroup as $og) {
                // For plans
                if ($coupon->product_type == self::SPECIFIC_TYPES['PLAN'] && $coupon->product_id == $og->plan_id) {
                    $planData = $this->couponForPlans($og, $isPercentage, $coupon, $tax);
                    if ($planData['discount'] == 0 || !$planData['discount']) continue;
                    $totalDiscount += $planData['discount'];
                    $orderCouponProduct[] = $planData['products'];
                }
                // For devices
                if ($coupon->product_type == self::SPECIFIC_TYPES['DEVICE'] && $coupon->product_id == $og->device_id) {
                    $deviceData = $this->couponForDevice($og, $isPercentage, $coupon, $tax);
                    if (!$deviceData['discount'] || $deviceData['discount'] == 0) continue;
                    $totalDiscount += $deviceData['discount'];
                    $orderCouponProduct[] = $deviceData['products'];
                }
                // For Sims
                if ($coupon->product_type == self::SPECIFIC_TYPES['SIM'] && $coupon->product_id == $og->sim_id) {
                    $simData = $this->couponForSims($og, $isPercentage, $coupon, $tax);
                    if ($simData['discount'] == 0 || !$simData['discount']) continue;
                    $totalDiscount += $simData['discount'];
                    $orderCouponProduct[] = $simData['products'];
                }
                // For Addons
                if ($coupon->product_type == self::SPECIFIC_TYPES['ADDON'] && $og->addons->count()) {
                    foreach ($og->addons as $addon) {
                        if ($addon->id != $coupon->product_id) continue;
                        $addonData = $this->couponForAddons($order, $addon, $isPercentage, $coupon, $og, $tax);
                        if ($addonData['discount'] == 0 || !$addonData['discount']) continue;
                        $totalDiscount += $addonData['discount'];
                        $orderCouponProduct[] = $addonData['products'];
                    }
                }
            }
        }
        isset($orderCouponProduct) ? $this->orderCoupon($orderCouponProduct, $order) : null;
        return ([
            'total'         => str_replace(',', '', $totalDiscount),
            'applied_to'    => isset($orderCouponProduct) ? $orderCouponProduct : [],
        ]);
    }

	/**
	 * @param $productType
	 * @param $productId
	 * @param $couponAmount
	 * @param $discount
	 * @param $orderGroupId
	 *
	 * @return array
	 */
	protected function orderCouponProducts($productType, $productId, $couponAmount, $discount, $orderGroupId)
    {
        return [
            'order_product_type'    => $productType,
            'order_product_id'      => $productId,
            'amount'                => $couponAmount,
            'discount'              => $discount,
            'order_group_id'        => $orderGroupId,
        ];
    }

	/**
	 * @param $data
	 * @param $order
	 */
	protected function orderCoupon($data, $order)
    {
	    $orderCoupons = $order->orderCoupon;
	    foreach($orderCoupons as $orderCoupon) {
		    $orderCoupon->orderCouponProduct()->delete();
		    if (count($data)) {
			    foreach ($data as $product) {
				    $orderCoupon->orderCouponProduct()->create([
					    'order_product_type'    => $product['order_product_type'],
					    'order_product_id'      => $product['order_product_id'],
					    'amount'                => $product['amount']
				    ]);
			    }
		    }
	    }
    }

	/**
	 * @param $og
	 * @param $isPercentage
	 * @param $coupon
	 * @param $tax
	 *
	 * @return array
	 */
	protected function couponForDevice($og, $isPercentage, $coupon, $tax)
    {
        $deviceAmount = $og->plan_id ? $og->device->amount_w_plan : $og->device->amount;
        // $deviceAmount += $og->device->taxable ? $deviceAmount * $tax / 100 : 0;
        $deviceDiscount = ($isPercentage ? $coupon->amount * $deviceAmount / 100 : $coupon->amount);

	    /**
	     * @internal Rule that coupon can never exceed the original cost
	     */
	    $discountAmount = $deviceDiscount > $deviceAmount ? $deviceAmount : $deviceDiscount;
        $orderCouponProduct = $this->orderCouponProducts(self::SPECIFIC_TYPES['DEVICE'], $og->device_id, $coupon->amount, $discountAmount, $og->id);
	    if($deviceAmount > $discountAmount){
		    $taxableAmount = $deviceAmount - $discountAmount;
	    } else {
		    $taxableAmount = $discountAmount;
	    }
        $this->totalTaxableAmount[] = $og->device->taxable ? $taxableAmount : 0;
        return ['discount' => $discountAmount, 'products' => $orderCouponProduct];
    }

	/**
	 * @param $og
	 * @param $isPercentage
	 * @param $coupon
	 * @param $tax
	 *
	 * @return array
	 */
	protected function couponForPlans($og, $isPercentage, $coupon, $tax)
    {
        $planAmount = $og->plan_prorated_amt ?: $og->plan->amount_recurring;
        $planAmount += $og->plan->amount_onetime ?: 0;
        // $planAmount += $og->plan->taxable && $tax ? $planAmount * $tax / 100 : 0;
        // $planAmount += $og->plan->getRegualtoryAmount($og->plan->id, $planAmount);

        $planDiscount = ($isPercentage ? $coupon->amount * $planAmount / 100 : $coupon->amount);
	    /**
	     * @internal Rule that coupon can never exceed the original cost
	     */
	    $discountAmount = $planDiscount > $planAmount ? $planAmount : $planDiscount;
        $orderCouponProduct = $this->orderCouponProducts(self::SPECIFIC_TYPES['PLAN'], $og->plan_id, $coupon->amount, $discountAmount, $og->id);
	    if($planAmount > $discountAmount){
		    $taxableAmount = $planAmount - $discountAmount;
	    } else {
		    $taxableAmount = $discountAmount;
	    }
        $this->totalTaxableAmount[] = $og->plan->taxable ? $taxableAmount : 0;
        return ['discount' => $discountAmount, 'products' => $orderCouponProduct];
    }

	/**
	 * @param $og
	 * @param $isPercentage
	 * @param $coupon
	 * @param $tax
	 *
	 * @return array
	 */
	protected function couponForSims($og, $isPercentage, $coupon, $tax)
    {
        $simAmount = $og->plan_id ? $og->sim->amount_w_plan : $og->sim->amount_alone;
        // $simAmount += $og->sim->taxable ? $simAmount * $tax / 100 : 0;
        $simDiscount =  ($isPercentage ? $coupon->amount * $simAmount / 100 : $coupon->amount);
	    /**
	     * @internal Rule that coupon can never exceed the original cost
	     */
	    $discountAmount = $simDiscount > $simAmount ? $simAmount : $simDiscount;
        $orderCouponProduct = $this->orderCouponProducts(self::SPECIFIC_TYPES['SIM'], $og->sim_id, $coupon->amount, $discountAmount, $og->id);
	    if($simAmount > $discountAmount){
		    $taxableAmount = $simAmount - $discountAmount;
	    } else {
		    $taxableAmount = $discountAmount;
	    }
        $this->totalTaxableAmount[] = $og->sim->taxable ? $taxableAmount : 0;
        return ['discount' => $discountAmount, 'products' => $orderCouponProduct];
    }


	/**
	 * @param $order
	 * @param $addon
	 * @param $isPercentage
	 * @param $coupon
	 * @param $og
	 * @param $tax
	 *
	 * @return array
	 */
	protected function couponForAddons($order, $addon, $isPercentage, $coupon, $og, $tax)
    {
        $addonAmount = $order->addonProRate($addon->id) ?: $addon->amount_recurring;
        // $addonAmount += $addon->taxable ? $addonAmount * $tax / 100 : 0;
        $addonDiscount  = ($isPercentage ? $coupon->amount * $addonAmount / 100 : $coupon->amount);
	    /**
	     * @internal Rule that coupon can never exceed the original cost
	     */
	    $discountAmount = $addonDiscount > $addonAmount ? $addonAmount : $addonDiscount;
        $orderCouponProduct = $this->orderCouponProducts(self::SPECIFIC_TYPES['ADDON'], $addon->id, $coupon->amount, $discountAmount, $og->id);
        if($addonAmount > $discountAmount){
	        $taxableAmount = $addonAmount - $discountAmount;
        } else {
	        $taxableAmount = $discountAmount;
        }

        $this->totalTaxableAmount[] = $addon->taxable ? $taxableAmount : 0;
        return ['discount' => $discountAmount, 'products' => $orderCouponProduct];
    }

	/**
	 * @param Request $request
	 */
	public function removeCoupon(Request $request)
    {
    	try {
		    $order = Order::find($request->order_id);
		    $couponCode = $request->get('coupon_code');
		    $coupon = Coupon::where('code', $couponCode)->first();
		    $couponToRemove = $order->orderCoupon->where('coupon_id', $coupon->id)->first();
		    return $couponToRemove ? ['status' => $couponToRemove->delete()] : ['status' => false];
	    } catch(Exception $e) {
		    \Log::info( $e->getMessage() . ' on line number: ' . $e->getLine() . ' in CouponController remove' );
		    return ['status' => false];
	    }

    }

	/**
	 * @param $coupon
	 *
	 * @return array
	 */
	public function checkEligibleProducts($coupon)
    {
        $planRestriction = [];
        $isPercentage    = $coupon->fixed_or_perc == self::FIXED_PERC_TYPES['percentage'] ? true : false;
        if ($coupon->multiline_restrict_plans && $coupon->multilinePlanTypes->count()) {
            foreach ($coupon->multilinePlanTypes as $type) {
                if ($type->plan_type == self::PLAN_TYPE['Voice']) {
                    $planRestriction[] = 'Voice';
                } elseif ($type->plan_type == self::PLAN_TYPE['Data']) {
                    $planRestriction[] = 'Data';
                }
            }
        }
        
        $planRestriction = count($planRestriction) ? implode(', ',$planRestriction) : false;
        if ($coupon->class == Coupon::CLASSES['APPLIES_TO_ALL']) {
            $amount = $isPercentage == false ? '$'.$coupon->amount : $coupon->amount.'%';
            return [
                'details'            => implode('<br>', [
                    $amount. ' off on all products',
                    $planRestriction ? 'Plan restriction :'.$planRestriction : null
                ])
            ];
        } elseif ($coupon->class == Coupon::CLASSES['APPLIES_TO_SPECIFIC_TYPES']) {
            $couponTypes = $coupon->couponProductTypes;
            foreach ($couponTypes as $type) {
                $amount = $type->amount;
                $type_amount =  $isPercentage == false ? '$'.$amount : $amount.'%';
                $plans[]   = $type->type == self::SPECIFIC_TYPES['PLAN']   ? $type_amount.' off on plans'   : '';
                $devices[] = $type->type == self::SPECIFIC_TYPES['DEVICE'] ? $type_amount.' off on devices' : '';
                $sims[]    = $type->type == self::SPECIFIC_TYPES['SIM']    ? $type_amount.' off on sims'    : '';
                $addons[]  = $type->type == self::SPECIFIC_TYPES['ADDON']  ? $type_amount.' off on addons'  : '';
            }
            return [
                'details' => implode('<br>', array_filter(
                    [
                        implode('', $plans), 
                        implode('', $devices), 
                        implode('', $sims), 
                        implode('', $addons), 
                        $planRestriction ? 'Plan restriction :'.$planRestriction : null,
                        $coupon->num_cycles ? 'Cycles: Coupon applies for '. $coupon->num_cycles. ' billing cycles' : 'Cycles: Infinite coupon'
                    ], 
                'strlen'))
            ];
        } elseif ($coupon->class == Coupon::CLASSES['APPLIES_TO_SPECIFIC_PRODUCT']) {
            $couponProducts = $coupon->couponProducts;
            $plans   = [];
            $devices = [];
            $sims   = [];
            $addons = [];
            foreach ($couponProducts as $product) {
                $amount = $product->amount;
                $amount =  $isPercentage == false ? '$'.$amount : $amount.'%';
                $plans[]   = $product->product_type == self::SPECIFIC_TYPES['PLAN']   ? $amount.' off on plan '. $product->plan->name   : '';
                $devices[] = $product->product_type == self::SPECIFIC_TYPES['DEVICE'] ? $amount. ' off on device '. $product->device->name : '';
                $sims[]    = $product->product_type == self::SPECIFIC_TYPES['SIM']    ? $amount. ' off on sim '. $product->sim->name    : '';
                $addons[]  = $product->product_type == self::SPECIFIC_TYPES['ADDON']  ? $amount. ' off on addon '. $product->addon->name  : '';
            }

            return [
                'details' => implode('<p></p><br>', array_filter(
                    [
                        implode(', ', array_filter($plans, 'strlen')), 
                        implode(', ', array_filter($devices, 'strlen')), 
                        implode(', ', array_filter($sims, 'strlen')), 
                        implode(', ', array_filter($addons, 'strlen')), 
                        $planRestriction ? 'Plan restriction :'.$planRestriction : null,
                        $coupon->num_cycles ? 'Cycles: Coupon applies for '. $coupon->num_cycles. ' billing cycles' : 'Cycles: Infinite coupon'
                    ], 
                'strlen'))
            ];
        }
    }

	/**
	 * @param $autoAddCoupon
	 * @param $orderGroups
	 *
	 * @return bool
	 */
	protected function ifAutoAdd($autoAddCoupon, $orderGroups)
    {
        if ($autoAddCoupon->count()) {
            $eligiblePlans = $autoAddCoupon->pluck('id')->toArray();
            $orderPlanIds  = $orderGroups->where('plan_id', '!=', null)->pluck('plan_id')->toArray();
            $eligibleOgs   = array_filter($orderPlanIds, function($id) use ($eligiblePlans) {
                if (in_array($id, $eligiblePlans)) {
                    return $id;
                }
            });
            if (!count($eligibleOgs)) {
                return false;
            }
        }
        return true;
    }

	/**
	 * Check if the coupon in the order are stackable or used
	 * @param $coupon
	 * @param $order_id
	 *
	 * @return bool
	 */
    protected function couponAreStackableAndUnused( $coupon, $order_id )
    {
	    if($coupon->stackable !== 1) {
		    $this->failedResponse = "Coupon {$coupon->code} is not stackable. If you still wish to add this coupon, remove the existing coupons and try again";
			return false;
	    }
	    $notStackableCouponCode = '';
	    $alreadyUsedCouponCode = '';
		$order = Order::find($order_id);
		$orderCoupons = $order->orderCoupon;

	    foreach ($orderCoupons as $orderCoupon ) {
	    	if($orderCoupon->coupon_id === $coupon->id){
			    $alreadyUsedCouponCode = $coupon->code;
			    break;
		    }
			if($orderCoupon->coupon->stackable !== 1){
				$notStackableCouponCode = $orderCoupon->code;
				break;
			}
	    }
	    if($alreadyUsedCouponCode){
		    $this->failedResponse = "Coupon $alreadyUsedCouponCode is already used.";
		    return false;
	    }

	    if($notStackableCouponCode){
		    $this->failedResponse = "Coupon $notStackableCouponCode is not stackable. If you still wish to add this coupon, remove the existing coupons and try again";
			return false;
	    }

	    return true;
    }

}