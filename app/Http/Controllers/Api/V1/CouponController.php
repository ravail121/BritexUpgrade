<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Model\Coupon;
use App\Model\Customer;
use App\Model\Plan;
use App\Model\OrderCoupon;
use App\Model\OrderGroup;
use App\Model\Order;
use App\Model\Device;
use App\Model\Sim;
use App\Model\Addon;
use Carbon\Carbon;

class CouponController extends Controller
{

    const COUPON_CLASS = [
        'APPLIES_TO_ALL'                => 1,
        'APPLIES_TO_SPECIFIC_TYPES'     => 2,
        'APPLIES_TO_SPECIFIC_PRODUCT'   => 3
    ];

    const SPECIFIC_TYPES = [
        'PLAN'      =>  1,
        'DEVICE'    =>  2,
        'SIM'       =>  3,
        'ADDON'     =>  4
    ];

    const SUB_TYPES = [
        'NOT_LIMITED'   => 0,
        'VOICE'         => 1,
        'DATA'          => 2,
        'WEARABLE'      => 3,
        'MEMBERSHIP'    => 4,
        'DIGITS'        => 5,
        'CLOUD'         => 6
    ];

    const ORDER_GROUP = [
        'PLAN'      =>  'plans',
        'DEVICE'    =>  'devices',
        'SIM'       =>  'sims',
        'ADDON'     =>  'addons'
    ];

    public function addCoupon(Request $request)
    {
        $coupon = Coupon::where('code', $request->code)->first();
        $order  = Order::find($request->order_id);

        if (!$coupon) {

            return ['error' => 'Coupon is invalid'];

        } else {

            $customerHash          = $request->hash ? $request->hash : $order->customer->hash;
            
            $billableSubscriptions = Customer::where('hash', $request->hash)->first()->billableSubscriptions;
            
            $billablePlans = [];

            foreach ($billableSubscriptions as $subscription) {

                $billablePlans[] = Plan::find($subscription->plan_id);

            }

            if (!empty($coupon)) {
                $alreadyExists = OrderCoupon::where('order_id', $request->order_id)->get();
                $orderGroup    = OrderGroup::where('order_id', $request->order_id)->sum('plan_prorated_amt');
                if (count($alreadyExists) < 1) {

                    OrderCoupon::create([
                        'order_id' => $request->order_id,
                        'coupon_id' => $coupon->id
                    ]);

                }
                $couponData = [
                    'coupon'                => $coupon,
                    'specificTypes'         => $coupon->couponProductTypes,
                    'specificProducts'      => $coupon->couponProducts,
                    'billablePlans'         => $billablePlans,
                    'orderGroup'            => $orderGroup
                ];
                
                $coupon                 = $couponData['coupon'];
                $couponProductTypes     = $couponData['specificProducts'];
                $couponSpecificTypes    = $couponData['specificTypes'];
                $orderGroupCart         = $request->orderGroupsCart;
                $billablePlans          = $couponData['billablePlans'];
                
                $cartPlans              = isset($orderGroupCart['plans']['items']) ? ['items' => $orderGroupCart['plans']['items']]    : [];

                $appliedToAll       = $coupon['class'] == self::COUPON_CLASS['APPLIES_TO_ALL']              ?  $this->appliedToAll($coupon, $order->id) : 0;
                $appliedToTypes     = $coupon['class'] == self::COUPON_CLASS['APPLIES_TO_SPECIFIC_TYPES']   ?  $this->appliedToTypes($coupon, $couponSpecificTypes, $order->id, $orderGroupCart) : 0;
                $appliedToProducts  = $coupon['class'] == self::COUPON_CLASS['APPLIES_TO_SPECIFIC_PRODUCT'] ?  $this->appliedToProducts($coupon, $couponProductTypes, $order->id, $orderGroupCart) : 0;
                
                if ($this->isApplicable($cartPlans, $billablePlans, $coupon)) {

                    if ($this->ifCouponCanBeUsed($coupon)) {

                        if ($this->ifCouponNotReachedMaxLimit($coupon)) {

                            $total = $appliedToAll + $appliedToTypes + $appliedToProducts;
                            
                            return $total;

                        } else {

                            return [
                                'coupon_max_used' => $coupon['num_uses']
                            ];

                        }


                    } else {

                        return [
                            'coupon_not_usable' => [
                                'start_date' => $coupon['start_date'],
                                'end_date'   => $coupon['end_date']
                            ]
                        ];

                    }

                } else {    
                    
                    return [
                        'not_eligible' => [
                            'multiline_min' => $coupon['multiline_min'],
                            'multiline_max' => $coupon['multiline_max'],
                            'active_subs'   => count($cartPlans + $billablePlans)
                        ]
                    ]; 
                
                }

            }
            
        }
        
    }

    protected function ifCouponNotReachedMaxLimit($coupon)
    {

        $maxLimitNotReached = true;

        if ($coupon['num_uses'] >= $coupon['max_uses']) {

            $maxLimitNotReached = false;

        }

        return $maxLimitNotReached;

    }

    protected function ifCouponCanBeUsed($coupon)
    {
        $isApplicable       = true;

        $today              = strtotime(Carbon::today());

        $couponStartDate    = strtotime(($coupon['start_date']));
        
        $couponExpiryDate   = strtotime(($coupon['end_date']));

        if ($couponStartDate && $couponExpiryDate) {

            if ($today < $couponStartDate || $today > $couponExpiryDate) {
                
                $isApplicable = false;
            }

        } elseif ($couponStartDate && !$couponExpiryDate) {

            if ($today < $couponStartDate) {

                $isApplicable = false;

            }

        } 

        return $isApplicable;

    }

    protected function isApplicable($cartPlans, $billablePlans, $coupon)
    {
        $isApplicable       = true;

        $totalSubscriptions = array_merge($cartPlans, $billablePlans);

        if (count($totalSubscriptions) < $coupon['multiline_min'] || count($totalSubscriptions) > $coupon['multiline_max']) {
            
            $isApplicable = false;
            
        }

        return $isApplicable;

    }

    protected function appliedToAll($coupon, $id)
    {
        $order              = Order::find($id);
        $orderGroup         = OrderGroup::where('order_id', $id)->get();
        $planAmount         = [];
        $deviceAmount       = [];
        $simAmount          = [];
        $addonAmount        = [];
        $isPercentage       = $coupon['fixed_or_perc'] == 2 ? true : false;
        $itemsAmount        = 0;
        $countItems         = [];
        $multilineRestrict  = $coupon['multiline_restrict_plans'] == 1 ? $coupon->multilinePlanTypes->pluck('plan_type') : null;
            
        foreach ($orderGroup as $og) {

            if ($og->plan_id) {

                $plan           = Plan::find($og->plan_id);

                $planType       = $plan->type;

                if ($multilineRestrict) {

                    if ($planType == $multilineRestrict[0]) {

                        $planAmount[]   = $og->plan_prorated_amt ? $og->plan_prorated_amt : $plan->amount_recurring;

                        $countItems[]   = $og->plan_id;

                    }

                } else {

                    $planAmount[]   = $og->plan_prorated_amt ? $og->plan_prorated_amt : $plan->amount_recurring;

                    $countItems[]   = $og->plan_id;

                }

                
                
            }

            if ($og->device_id) {

                $device         = Device::find($og->device_id);

                $deviceAmount[] = $og->plan_id ? $device->amount_w_plan : $device->amount;

                $countItems[]   = $og->device_id;

            }

            if ($og->sim_id) {

                $sim            = Sim::find($og->sim_id);

                $simAmount[]    = $og->plan_id ? $sim->amount_w_plan : $sim->amount_alone; 
                
                $countItems[]   = $og->sim_id;
                
            }

            $addonIds   = [];

            if ($og->plan_id) {

                foreach ($og->order_group_addon as $addon) {
      
                    $addonIds[] = $addon->addon_id;

                } 

            }

            foreach ($addonIds as $id) {

                $addon = Addon::find($id);

                $addonAmount[] = $order->addonProRate($id) > 0 ? $order->addonProRate($id) : $addon->amount_recurring;

                $countItems[]  = $id;

            }
            
        }

        $itemsAmount = array_sum($planAmount) + array_sum($deviceAmount) + array_sum($simAmount) + array_sum($addonAmount);

        $total       = $isPercentage ? $itemsAmount * $coupon['amount'] / 100 : $coupon['amount'] * count($countItems);

        return ($total);

    }

    protected function ifPlanApplicable($multilineRestrict, $plans)
    {

        $applicablePlans = [];
        
        if ($multilineRestrict) {
            
            foreach ($plans as $plan) {
            
                $planType = $plan['type'];
                
                if ($planType == $multilineRestrict[0]) {

                    $applicablePlans[] = $plan;

                }

            }

        }

       return $applicablePlans;

    }

    protected function appliedToTypes($couponMain, $couponSpecificTypes, $id, $orderGroupCart)
    {
        
        $order              = Order::find($id);
        $orderGroup         = OrderGroup::where('order_id', $id)->get();
        $isPercentage       = $couponMain['fixed_or_perc'] == 2 ? true : false;
        $couponAmount       = [];
        $multilineRestrict  = $couponMain['multiline_restrict_plans'] == 1 ? $couponMain->multilinePlanTypes->pluck('plan_type') : null;
        $orderGroupPlans    = isset($orderGroupCart['plans']['items']) ? $orderGroupCart['plans']['items'] : [];
        $ifPlanApplicable   = $this->ifPlanApplicable($multilineRestrict, $orderGroupPlans);
        $plans              = $couponMain   ['multiline_restrict_plans'] > 0 ? $ifPlanApplicable : $orderGroupPlans;
    
        foreach ($couponSpecificTypes as $coupon) {
            //For plan types
            if ($coupon['type'] == self::SPECIFIC_TYPES['PLAN']) {

                $isLimited = $coupon['sub_type'] > 0 ? $coupon['sub_type'] : false;

                if (isset($orderGroupCart['devices']['items'])) {

                    foreach ($plans as $plan) {
                    
                        $isProrated = $order->planProRate($plan['id']);
                        
                        $plan       = Plan::find($plan['id']);
    
                        if ($isLimited) {
    
                            if ($plan['type'] == $isLimited) {
        
                                $planAmount   = $isProrated ? $isProrated : $plan->amount_recurring;
    
                                $couponAmount[] = $isPercentage ? $coupon['amount'] * $planAmount / 100 : $coupon['amount'];
    
                            }
    
                        } else {
    
                            $planAmount = $isProrated ? $isProrated : $plan->amount_recurring;
    
                            $couponAmount[] = $isPercentage ? $coupon['amount'] * $planAmount / 100 : $coupon['amount'];
    
                        }
    
                    }

                }

            }
            //For Device types
            if ($coupon['type'] == self::SPECIFIC_TYPES['DEVICE']) {

                if (isset($orderGroupCart['devices']['items'])) {

                    foreach ($orderGroupCart['devices']['items'] as $device) {

                        $orderGroup     = OrderGroup::find($device['order_group_id']);

                        $deviceAmount   = $orderGroup->plan_id ? $device['amount_w_plan'] : $device['amount'];

                        $couponAmount[] = $isPercentage ? $coupon['amount'] * $deviceAmount / 100 : $coupon['amount'];

                    }

                }

            }
            //For Sim types
            if ($coupon['type'] == self::SPECIFIC_TYPES['SIM']) {

                if (isset($orderGroupCart['sims']['items'])) {

                    foreach ($orderGroupCart['sims']['items'] as $sim) {

                        $orderGroup     = OrderGroup::find($sim['order_group_id']);

                        $simAmount      = $orderGroup->plan_id ? $sim['amount_w_plan'] : $sim['amount_alone'];

                        $couponAmount[] = $isPercentage ? $coupon['amount'] * $simAmount / 100 : $coupon['amount'];

                    }
                }

            }

            //For Addon types
            $orderGroupIds = [];
            if ($coupon['type'] == self::SPECIFIC_TYPES['ADDON'] && isset($orderGroupCart['addons']['items'])) {

                if (isset($orderGroupCart['addons']['items'])) {

                    foreach ($orderGroupCart['addons']['items'] as $item) {
                        
                        $orderGroupAddon = OrderGroup::find($item['order_group_id'])->order_group_addon;

                        foreach ($orderGroupAddon as $addon) {

                            $isProrated     = $order->addonProRate($addon->addon_id);

                            $addonAmount    = $isProrated ? $isProrated : Addon::find($addon->addon_id)->amount_recurring;

                            $couponAmount[] = $isPercentage ? $coupon['amount'] * $addonAmount / 100 : $coupon['amount'];
                            
                        }
                        
                    }

                }

            }

        }
 
        return (array_sum($couponAmount));

    }

    protected function appliedToProducts($couponMain, $couponProductTypes, $id, $orderGroupCart)
    {

        $order              = Order::find($id);
        $orderGroup         = OrderGroup::where('order_id', $id)->get();
        $isPercentage       = $couponMain['fixed_or_perc'] == 2 ? true : false;
        $couponAmount       = [];
        $multilineRestrict  = $couponMain['multiline_restrict_plans'] == 1 ? $couponMain->multilinePlanTypes->pluck('plan_type') : null;
        $orderGroupPlans    = isset($orderGroupCart['plans']['items']) ? $orderGroupCart['plans']['items'] : [];
        $ifPlanApplicable   = $this->ifPlanApplicable($multilineRestrict, $orderGroupPlans);
        $plans              = $couponMain['multiline_restrict_plans'] > 0 ? $ifPlanApplicable : $orderGroupPlans;

        foreach ($couponProductTypes as $coupon) {

            $productId = $coupon['product_id'];

            if ($coupon['product_type'] == self::SPECIFIC_TYPES['PLAN']) {

                if (isset($orderGroupCart['plans']['items'])) {

                    foreach ($plans as $plan) {
                    
                        if ($plan['id'] == $productId) {
                            
                            $isProrated     = $order->planProRate($plan['id']);
    
                            $planAmount     = $isProrated ? $isProrated : Plan::find($plan['id'])->amount_recurring;
                            
                            $couponAmount[] = $isPercentage ? $coupon['amount'] * $planAmount / 100 : $coupon['amount'];
    
                        }
    
                    }

                }

            }

            if ($coupon['product_type'] == self::SPECIFIC_TYPES['DEVICE']) {

                if (isset($orderGroupCart['devices']['items'])) {

                    foreach ($orderGroupCart['devices']['items'] as $device) {

                        if ($device['id'] == $productId) {

                            $orderGroup     = OrderGroup::find($device['order_group_id']);

                            $deviceAmount   = $orderGroup->plan_id ? $device['amount_w_plan'] : $device['amount'];

                            $couponAmount[] = $isPercentage ? $coupon['amount'] * $deviceAmount / 100 : $coupon['amount'];

                        }

                    }

                }

            }

            if ($coupon['product_type'] == self::SPECIFIC_TYPES['SIM']) {

                if (isset($orderGroupCart['sims']['items'])) {

                    foreach ($orderGroupCart['sims']['items'] as $sim) {

                        if ($sim['id'] == $productId) {

                            $orderGroup     = OrderGroup::find($sim['order_group_id']);

                            $simAmount      = $orderGroup->plan_id ? $sim['amount_w_plan'] : $sim['amount_alone'];

                            $couponAmount[] = $isPercentage ? $coupon['amount'] * $simAmount / 100 : $coupon['amount'];

                        }

                    }

                }


            }

            $orderGroupIds = [];

            if ($coupon['product_type'] == self::SPECIFIC_TYPES['ADDON'] && isset($orderGroupCart['addons']['items'])) {

                if (isset($orderGroupCart['addons']['items'])) {

                    foreach ($orderGroupCart['addons']['items'] as $item) {
                        
                        $orderGroupAddon = OrderGroup::find($item['order_group_id'])->order_group_addon;

                        foreach ($orderGroupAddon as $addon) {

                            if ($addon->addon_id == $productId) {

                                $isProrated     = $order->addonProRate($addon->addon_id);

                                $addonAmount    = $isProrated ? $isProrated : Addon::find($addon->addon_id)->amount_recurring;

                                $couponAmount[] = $isPercentage ? $coupon['amount'] * $addonAmount / 100 : $coupon['amount'];

                            }
                            
                        }

                    }

                }
                
            }

        }
        return (array_sum($couponAmount));
    }

}
