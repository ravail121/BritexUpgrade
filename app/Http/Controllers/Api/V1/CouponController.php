<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Model\Coupon;
use App\Model\Customer;
use App\Model\Plan;
use App\Model\OrderCoupon;
use App\Model\OrderGroup;

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
        
        if (!$coupon) {

            return ['error' => 'Coupon is invalid'];

        } else {

            $multiline = $coupon->multiline_restrict_plans ? $coupon->multilinePlanTypes->pluck('plan_type') : null;
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
                    'billableSubscription'  => $billableSubscriptions,
                    'billablePlans'         => $billablePlans,
                    'multiline_type'        => $multiline,
                    'orderGroup'            => $orderGroup
                ];
                
                $coupon                 = $couponData['coupon'];
                $couponProductTypes     = $couponData['specificProducts'];
                $couponSpecificTypes    = $couponData['specificTypes'];
                $orderGroupCart         = $request->orderGroupsCart;
                $billableSubscriptions  = $couponData['billableSubscription'];
                $billablePlans          = $couponData['billablePlans'];
                $multilineType          = $couponData['multiline_type'];
                $proratedAmount         = $couponData['orderGroup'];
                
                //gets coupon discount if product_type is eligible
                $cartPlans      = isset($orderGroupCart['plans'])   ? $orderGroupCart['plans']   : [];
                $devices        = isset($orderGroupCart['devices']) ? $orderGroupCart['devices'] : [];
                $sims           = isset($orderGroupCart['sims'])    ? $orderGroupCart['sims']    : [];
                $addons         = isset($orderGroupCart['addons'][0])  ? $orderGroupCart['addons'][0]  : [];

                $planCoupons    = $this->couponAmount($coupon, $couponProductTypes, $couponSpecificTypes, $cartPlans, $proratedAmount, self::SPECIFIC_TYPES['PLAN']);
                $deviceCoupons  = $this->couponAmount($coupon, $couponProductTypes, $couponSpecificTypes, $devices, $proratedAmount, self::SPECIFIC_TYPES['DEVICE']);
                $simCoupons     = $this->couponAmount($coupon, $couponProductTypes, $couponSpecificTypes, $sims, $proratedAmount, self::SPECIFIC_TYPES['SIM']);
                $addonCoupons   = $this->couponAmount($coupon, $couponProductTypes, $couponSpecificTypes, $addons, $proratedAmount, self::SPECIFIC_TYPES['ADDON']);
            
                if ($this->isApplicable($cartPlans, $multilineType, $billablePlans, $coupon)) {

                    $total = number_format($deviceCoupons + $planCoupons + $addonCoupons + $simCoupons, 2);
                    return $total;

                } else {
                    
                    return [
                        'not_eligible' => 'You are not eligible for this coupon'
                    ]; 
                
                }
            }
        }
        
    }

    protected function isApplicable($cartPlans, $multilineType, $billablePlans, $coupon)
    {
        $isApplicable = true;
        
        $totalSubscriptions = array_merge($cartPlans, $billablePlans);

        $totalApplicableSubscriptions = [];
        
        if ($multilineType[0] == 1) {
            
            foreach ($totalSubscriptions as $sub) {
                if ($sub['type'] == $multilineType[0]) {
                    $totalApplicableSubscriptions[] = $sub;
                }
            }
            
            if (count($totalApplicableSubscriptions) < $coupon['multiline_min'] || count($totalApplicableSubscriptions) > $coupon['multiline_max']) {

                $isApplicable = false;
                
            } 

        } else {
            
            if (count($totalSubscriptions) < $coupon['multiline_min'] || count($totalSubscriptions) > $coupon['multiline_max']) {
                
                $isApplicable = false;

            } 

        }
        
        return $isApplicable;

    }

    protected function couponAmount($coupon, $couponProductTypes, $couponSpecificTypes, $itemType, $proratedAmount, $itemSubType)
    {
        $amount = [];
        $total = 0;

        foreach ($itemType as $item) {

            if ($coupon['class'] == self::COUPON_CLASS['APPLIES_TO_ALL']) {

                $amount[] = $coupon['amount'];

            } elseif ($coupon['class'] == self::COUPON_CLASS['APPLIES_TO_SPECIFIC_TYPES']) {

                foreach ($couponSpecificTypes as $type) {

                    $subtype = $type['sub_type'];

                    if ($type['type'] == $itemSubType) {

                        if ($subtype == self::SUB_TYPES['NOT_LIMITED']) {

                            $amount[] = $type['amount'];

                        } else {

                            if (isset($item['type']) && $item['type'] == $subtype) {

                                $amount[] = $type['amount'];

                            }

                        }

                    }
                }

            } elseif ($coupon['class'] == self::COUPON_CLASS['APPLIES_TO_SPECIFIC_PRODUCT']) {

                foreach ($couponProductTypes as $type) {

                    if ($type['product_type'] == $itemSubType) {

                        if ($type['product_id'] == $item['id']) {

                            $amount[] = $type['amount'];

                        }

                    }

                }

            }

            if ($coupon['fixed_or_perc'] == 1) {

                $total = $proratedAmount > 0 ? $proratedAmount * (array_sum($amount) / 100) : array_sum($amount);

            } else {

                $total = array_sum($amount);

            }

        }

        return $total;

    }

}
