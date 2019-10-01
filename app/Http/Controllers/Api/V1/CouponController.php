<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Model\Coupon;
use App\Model\Plan;
use App\Model\OrderCoupon;
use App\Model\OrderGroup;
use App\Model\Order;
use App\Model\Device;
use App\Model\Sim;
use App\Model\Addon;
use Carbon\Carbon;
use Exception;
use App\Model\Customer;

class CouponController extends Controller
{

    const SPECIFIC_TYPES = [
        'PLAN'      =>  1,
        'DEVICE'    =>  2,
        'SIM'       =>  3,
        'ADDON'     =>  4
    ];

    protected $failedResponse;
    
    public function addCoupon(Request $request)
    {
        try {
            $coupon = Coupon::where('code', $request->code)->first();
            $order  = Order::find($request->order_id);
            $customer = Customer::where('hash', $request->hash)->first();
            if (!$this->couponIsValid($coupon)) {
                return ['error' => $this->failedResponse];
            } else {
                OrderCoupon::updateOrCreate([
                    'order_id' => $order->id,
                    'coupon_id' => $coupon->id
                ]);
                $orderGroupCart     = $request->orderGroupsCart;
                $cartPlans          = isset($orderGroupCart['plans']['items']) ? ['items' => $orderGroupCart['plans']['items']] : [];
                $appliedToAll       = $coupon['class'] == Coupon::CLASSES['APPLIES_TO_ALL']              ?  $this->appliedToAll($coupon, $order->id) : 0;
                $appliedToTypes     = $coupon['class'] == Coupon::CLASSES['APPLIES_TO_SPECIFIC_TYPES']   ?  $this->appliedToTypes($coupon, $coupon->couponProductTypes, $order->id, $orderGroupCart) : 0;
                $appliedToProducts  = $coupon['class'] == Coupon::CLASSES['APPLIES_TO_SPECIFIC_PRODUCT'] ?  $this->appliedToProducts($coupon, $coupon->couponProducts, $order->id, $orderGroupCart) : 0;                
                
                if ($this->isApplicable($cartPlans, $customer->billableSubscriptionsForCoupons, $coupon)) {
                    $total = $appliedToAll['total'] + $appliedToTypes['total'] + $appliedToProducts['total'];
                    return ['total' => $total, 'code' => $coupon->code, 'applied_to' => [
                            'applied_to_all'        => $appliedToAll['applied_to'],
                            'applied_to_types'      => $appliedToTypes['applied_to'],
                            'applied_to_products'   => $appliedToProducts['applied_to'],
                        ]
                    ];
                } else {
                    return ['error' => $this->failedResponse];
                }
            }
        } catch (Exception $e) {
            \Log::info($e->getMessage().' on line number: '.$e->getLine().' in CouponController');
            return [ 'total' => 0,'error' => 'Server error' ];
        }    
    }

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

    protected function isApplicable($cartPlans, $billableSubscriptions, $coupon)
    {
        $totalSubscriptions = count($cartPlans) + count($billableSubscriptions);
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

    protected function couponCanBeUsed($coupon)
    {
        $today              = Carbon::now();
        $couponStartDate    = Carbon::parse($coupon['start_date']);
        $couponExpiryDate   = Carbon::parse($coupon['end_date']);
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

    protected function appliedToAll($coupon, $id)
    {
        $order              = Order::find($id);
        $orderGroup         = $order->allOrderGroup;
        $isPercentage       = $coupon['fixed_or_perc'] == 2 ? true : false;
        $multilineRestrict  = $coupon['multiline_restrict_plans'] == 1 ? $coupon->multilinePlanTypes->pluck('plan_type') : null;
        $countItems         = 0;
        $totalDiscount      = 0;
        foreach ($orderGroup as $og) {
            // device charges
            if ($og->device_id) {
                $device         = Device::find($og->device_id);
                $deviceCharge   = $og->plan_id ? $device->amount_w_plan : $device->amount;
                $totalDiscount  += $deviceCharge;
                $deviceDiscount = number_format($isPercentage ? $deviceCharge * $coupon['amount'] / 100 : $coupon['amount'], 2);
                $orderCouponProduct[] = $this->orderCouponProducts(self::SPECIFIC_TYPES['DEVICE'], $device->id, $coupon['amount'], $deviceDiscount, $order, $og->id);
                $countItems++;
            }
            // plan charges
            if ($og->plan_id) {
                $plan           = Plan::find($og->plan_id);
                $planCharge     = $og->plan_prorated_amt ? $og->plan_prorated_amt : $plan->amount_recurring;
                if ($multilineRestrict) {
                    $planDiscount   = $multilineRestrict->contains($plan->type) ? number_format($isPercentage ? $planCharge * $coupon['amount'] / 100 : $coupon['amount'], 2) : 0;
                    $totalDiscount  += $planDiscount ? $planCharge : 0;
                } else {
                    $planDiscount   = number_format($isPercentage ? $planCharge * $coupon['amount'] / 100 : $coupon['amount'], 2);
                    $totalDiscount  += $planDiscount ? $planCharge : 0;
                }
                $orderCouponProduct[] = $planDiscount ? $this->orderCouponProducts(self::SPECIFIC_TYPES['PLAN'], $plan->id, $coupon['amount'], $planDiscount, $order, $og->id) : [];
                
                $planDiscount ? $countItems++ : null;
            }
            // sim charges
            if ($og->sim_id) {
                $sim            = Sim::find($og->sim_id);
                $simCharge      = $og->plan_id ? $sim->amount_w_plan : $sim->amount_alone; 
                $totalDiscount += $simCharge;
                $simDiscount    = number_format($isPercentage ? $simCharge * $coupon['amount'] / 100 : $coupon['amount'], 2);
                $orderCouponProduct[] = $this->orderCouponProducts(self::SPECIFIC_TYPES['SIM'], $sim->id, $coupon['amount'], $simDiscount, $order, $og->id);
                $countItems++;
            }
            // addon charges
            if ($og->plan_id) {
                foreach ($og->order_group_addon as $ogAddon) {
                    $addon = Addon::find($ogAddon->addon_id);
                    $totalDiscount += $order->addonProRate($addon->id);
                    $addonDiscount = number_format($isPercentage ? $order->addonProRate($id) * $coupon['amount'] / 100 : $coupon['amount'], 2);
                    $orderCouponProduct[] =  $this->orderCouponProducts(self::SPECIFIC_TYPES['ADDON'], $addon->id, $coupon['amount'], $addonDiscount, $order, $og->id);
                    $countItems++;
                }
            }
        }
        $total = $isPercentage ? $totalDiscount * $coupon['amount'] / 100 : $coupon['amount'] * $countItems;
        isset($orderCouponProduct) ? $this->orderCoupon($orderCouponProduct, $order, $coupon) : null;
        return (['total' => $total, 'applied_to' => isset($orderCouponProduct) ? $orderCouponProduct : [], 'order' => $order]);
    }


    protected function ifPlanApplicable($multilineRestrict, $plans)
    {
        $applicablePlans = [];
        if ($multilineRestrict) {
            foreach ($plans as $plan) {
                if ($multilineRestrict->contains($plan['type'])) {
                    $applicablePlans[] = $plan;
                }
            }
        }
       return $applicablePlans;
    }

    protected function appliedToTypes($couponMain, $couponSpecificTypes, $id, $orderGroupCart)
    {
        
        $order              = Order::find($id);
        $orderGroup         = $order->allOrderGroup;
        $isPercentage       = $couponMain->fixed_or_perc == 2 ? true : false;
        $multilineRestrict  = $couponMain->multiline_restrict_plans ? $couponMain->multilinePlanTypes->pluck('plan_type') : null;
        $orderGroupPlans    = isset($orderGroupCart['plans']['items']) ? $orderGroupCart['plans']['items'] : [];
        $ifPlanApplicable   = $this->ifPlanApplicable($multilineRestrict, $orderGroupPlans);
        $plans              = $multilineRestrict ? $ifPlanApplicable : $orderGroupPlans;
        $totalDiscount      = 0;
     
        foreach ($couponSpecificTypes as $coupon) {
            //For Device types
            if ($coupon->type == self::SPECIFIC_TYPES['DEVICE'] && isset($orderGroupCart['devices']['items'])) {
                foreach ($orderGroupCart['devices']['items'] as $device) {
                    $orderGroup     = OrderGroup::find($device['order_group_id']);
                    $deviceAmount   = $orderGroup->plan_id ? $device['amount_w_plan'] : $device['amount'];
                    $deviceDiscount = number_format($isPercentage ? $coupon['amount'] * $deviceAmount / 100 : $coupon['amount'], 2);
                    if ($deviceAmount) {
                        $totalDiscount += $deviceDiscount;
                        $orderCouponProduct[] = $this->orderCouponProducts(self::SPECIFIC_TYPES['DEVICE'], $device['id'], $couponMain['amount'], $deviceDiscount, $order, $orderGroup->id);
                    }
                }
            }
            //For plan types
            if ($coupon['type'] == self::SPECIFIC_TYPES['PLAN']) {
                $isLimited = $coupon['sub_type'] > 0 ? $coupon['sub_type'] : false;
                if (isset($orderGroupCart['plans']['items'])) {
                    foreach ($plans as $plan) {
                        $planData   = Plan::find($plan['id']);
                        $planAmount = $order->planProRate($plan['id']) ? $order->planProRate($plan['id']) : $planData->amount_recurring;
                        $planDiscount = number_format($isPercentage ? $coupon['amount'] * $planAmount / 100 : $coupon['amount'], 2);
                        if ($isLimited) {
                            if ($planData['type'] == $isLimited) {
                                $totalDiscount += $planDiscount;
                                $orderCouponProduct[]   = $this->orderCouponProducts(self::SPECIFIC_TYPES['PLAN'], $planData->id, $couponMain['amount'], $planDiscount, $order, $plan['order_group_id']);
                            }
                        } else {
                            $totalDiscount += $planDiscount;
                            $orderCouponProduct[]   = $this->orderCouponProducts(self::SPECIFIC_TYPES['PLAN'], $planData->id, $couponMain['amount'], $planDiscount, $order, $plan['order_group_id']);
                        }
                    }
                }
            }
            
            //For Sim types
            if ($coupon['type'] == self::SPECIFIC_TYPES['SIM']) {
                if (isset($orderGroupCart['sims']['items'])) {
                    foreach ($orderGroupCart['sims']['items'] as $sim) {
                        $orderGroup   = OrderGroup::find($sim['order_group_id']);
                        $simAmount    = $orderGroup->plan_id ? $sim['amount_w_plan'] : $sim['amount_alone'];
                        $simDiscount  = number_format($isPercentage ? $coupon['amount'] * $simAmount / 100 : $coupon['amount'], 2);
                        if ($simAmount) {
                            $totalDiscount += $simDiscount;
                            $orderCouponProduct[]   = $this->orderCouponProducts(self::SPECIFIC_TYPES['SIM'], $sim['id'], $couponMain['amount'], $simDiscount, $order, $sim['order_group_id']);
                        }
                    }
                }
            }

            //For Addon types
            if ($coupon['type'] == self::SPECIFIC_TYPES['ADDON'] && isset($orderGroupCart['addons']['items'])) {
                if (isset($orderGroupCart['addons']['items'])) {
                    foreach ($orderGroupCart['addons']['items'] as $item) {
                        $orderGroupAddon = OrderGroup::find($item['order_group_id'])->order_group_addon;
                        foreach ($orderGroupAddon as $addon) {
                            $addonAmount    = $order->addonProRate($addon->addon_id) ? $order->addonProRate($addon->addon_id) : Addon::find($addon->addon_id)->amount_recurring;
                            $addonDiscount  = number_format($isPercentage ? $coupon['amount'] * $addonAmount / 100 : $coupon['amount'], 2);
                            $totalDiscount += $addonDiscount;
                            $orderCouponProduct[]   = $this->orderCouponProducts(self::SPECIFIC_TYPES['ADDON'], $addon['id'], $couponMain['amount'], $addonDiscount, $order, $item['order_group_id']);
                        }
                    }
                }
            }
        }
        isset($orderCouponProduct) ? $this->orderCoupon($orderCouponProduct, $order, $couponMain) : null;
        return (['total' => $totalDiscount, 'applied_to' => isset($orderCouponProduct) ? $orderCouponProduct : [], 'order' => $order]);
    }

    protected function appliedToProducts($couponMain, $couponProductTypes, $id, $orderGroupCart)
    {
        $order              = Order::find($id);
        $orderGroup         = $order->allOrderGroup;
        $isPercentage       = $couponMain['fixed_or_perc'] == 2 ? true : false;
        $multilineRestrict  = $couponMain['multiline_restrict_plans'] == 1 ? $couponMain->multilinePlanTypes->pluck('plan_type') : null;
        $orderGroupPlans    = isset($orderGroupCart['plans']['items']) ? $orderGroupCart['plans']['items'] : [];
        $ifPlanApplicable   = $this->ifPlanApplicable($multilineRestrict, $orderGroupPlans);
        $plans              = $couponMain['multiline_restrict_plans'] > 0 ? $ifPlanApplicable : $orderGroupPlans;
        $totalDiscount      = 0;

        foreach ($couponProductTypes as $coupon) {
            // For plans
            if ($coupon['product_type'] == self::SPECIFIC_TYPES['PLAN']) {
                if (isset($orderGroupCart['plans']['items'])) {
                    foreach ($plans as $plan) {
                        if ($plan['id'] == $coupon['product_id']) {
                            $planAmount     = $order->planProRate($plan['id']) ? $order->planProRate($plan['id']) : Plan::find($plan['id'])->amount_recurring;
                            $planDiscount   = number_format($isPercentage ? $coupon['amount'] * $planAmount / 100 : $coupon['amount'], 2);
                            $totalDiscount += $planDiscount;
                            $orderCouponProduct[] = $this->orderCouponProducts(self::SPECIFIC_TYPES['PLAN'], $plan['id'], $couponMain['amount'], $planDiscount, $order, $plan['order_group_id']);
                        }
                    }
                }
            }
            // For devices
            if ($coupon['product_type'] == self::SPECIFIC_TYPES['DEVICE']) {
                if (isset($orderGroupCart['devices']['items'])) {
                    foreach ($orderGroupCart['devices']['items'] as $device) {
                        if ($device['id'] == $coupon['product_id']) {
                            $orderGroup     = OrderGroup::find($device['order_group_id']);
                            $deviceAmount   = $orderGroup->plan_id ? $device['amount_w_plan'] : $device['amount'];
                            if ($deviceAmount) {
                                $deviceDiscount = number_format($isPercentage ? $coupon['amount'] * $deviceAmount / 100 : $coupon['amount'], 2);
                                $totalDiscount += $deviceDiscount;
                                $orderCouponProduct[] = $this->orderCouponProducts(self::SPECIFIC_TYPES['DEVICE'], $device['id'], $couponMain['amount'], $deviceDiscount, $order, $device['order_group_id']);
                            }
                        }
                    }
                }
            }
            // For sims
            if ($coupon['product_type'] == self::SPECIFIC_TYPES['SIM']) {
                if (isset($orderGroupCart['sims']['items'])) {
                    foreach ($orderGroupCart['sims']['items'] as $sim) {
                        if ($sim['id'] == $coupon['product_id']) {
                            $orderGroup     = OrderGroup::find($sim['order_group_id']);
                            $simAmount      = $orderGroup->plan_id ? $sim['amount_w_plan'] : $sim['amount_alone'];
                            if ($simAmount) {
                                $simDiscount = number_format($isPercentage ? $coupon['amount'] * $simAmount / 100 : $coupon['amount'], 2);
                                $totalDiscount += $simDiscount;
                                $orderCouponProduct[]   = $this->orderCouponProducts(self::SPECIFIC_TYPES['SIM'], $sim['id'], $couponMain['amount'], $simDiscount, $order, $sim['order_group_id']);
                            }
                        }
                    }
                }
            }
            // For addons
            if ($coupon['product_type'] == self::SPECIFIC_TYPES['ADDON'] && isset($orderGroupCart['addons']['items'])) {
                if (isset($orderGroupCart['addons']['items'])) {
                    foreach ($orderGroupCart['addons']['items'] as $item) {
                        $orderGroupAddon = OrderGroup::find($item['order_group_id'])->order_group_addon;
                        foreach ($orderGroupAddon as $addon) {
                            if ($addon->addon_id == $coupon['product_id']) {
                                $isProrated     = $order->addonProRate($addon->addon_id);
                                $addonAmount    = $isProrated ? $isProrated : Addon::find($addon->addon_id)->amount_recurring;
                                $addonDiscount  = number_format($isPercentage ? $coupon['amount'] * $addonAmount / 100 : $coupon['amount'], 2);
                                $totalDiscount += $addonDiscount;
                                $orderCouponProduct[]   = $this->orderCouponProducts(self::SPECIFIC_TYPES['ADDON'], $addon->addon_id, $couponMain['amount'], $addonDiscount, $order, $item['order_group_id']);
                            }
                        }
                    }
                }
            }
        }
        isset($orderCouponProduct) ? $this->orderCoupon($orderCouponProduct, $order, $couponMain) : null;
        return (['total' => $totalDiscount, 'applied_to' => isset($orderCouponProduct) ? $orderCouponProduct : [], 'order' => $order]);
    }

    protected function orderCouponProducts($productType, $productId, $couponAmount, $discount, $order, $orderGroupId)
    {
        return [
            'order_product_type'    => $productType,
            'order_product_id'      => $productId,
            'amount'                => $couponAmount,
            'discount'              => $discount,
            'order'                 => $order,
            'order_group_id'        => $orderGroupId
        ];
    }

    protected function orderCoupon($data, $order, $coupon)
    {
        $order->orderCoupon->orderCouponProduct()->delete();
        // isset($order->orderCoupon) ? $order->orderCoupon()->delete() : null;
        if (count($data) && isset($order->orderCoupon)) {
            foreach ($data as $product) {
                if (isset($product['order']['id'])) {
                    $order->orderCoupon->orderCouponProduct()->create([
                        'order_product_type'    => $product['order_product_type'],
                        'order_product_id'      => $product['order_product_id'],
                        'amount'                => $product['amount']
                    ]);
                }
            }
        }
    }

}
