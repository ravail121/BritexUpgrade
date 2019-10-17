<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Model\Coupon;
use App\Model\OrderCoupon;
use App\Model\Order;
use Carbon\Carbon;
use Exception;
use App\Model\Customer;
use App\Model\Subscription;
use App\Model\SubscriptionCoupon;
use App\Http\Controllers\Api\V1\Traits\InvoiceCouponTrait;
use App\Model\CustomerCoupon;

class CouponController extends Controller
{
    use InvoiceCouponTrait;

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
            if (!$this->couponIsValid($coupon)) {
                return ['error' => $this->failedResponse];
            }
            if ($request->subscription_id) {
                return $this->ifAddedFromAdmin($request, $coupon);
            } else {
                return $this->ifAddedByCustomer($request, $coupon);
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

    protected function isApplicable($order, $customer, $coupon)
    {
        $totalSubscriptions = !$order ? 0 : $order->allOrderGroup->where('plan_id', '!=', null)->count() + $customer->billableSubscriptionsForCoupons->count();
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

    public function ifAddedFromAdmin($request, $coupon)
    {
        $subscription = Subscription::find($request->subscription_id);
        $customer = $subscription->customerRelation;
        if (!$this->isApplicable(false, $customer, $coupon)) {
            return ['error' => $this->failedResponse];
        }
        if ($this->ifMultiline($coupon)) {
            CustomerCoupon::create([
                'customer_id' => $customer->id,
                'coupon_id' => $coupon->id,
                'cycles_remaining'  => $coupon->num_cycles
            ]);
            return ['success' => 'Multiline coupon added'];
        } else {
            SubscriptionCoupon::create([
                'subscription_id' => $subscription->id,
                'coupon_id' => $coupon->id,
                'cycles_remaining' => $coupon->num_cycles
            ]);
            return ['success' => 'Subscription coupon added'];
        }
    }

    public function ifAddedByCustomer($request, $coupon)
    {
        $order  = Order::find($request->order_id);
        $customer = Customer::where('hash', $request->hash)->first();
        OrderCoupon::updateOrCreate([
            'order_id' => $order->id,
            'coupon_id' => $coupon->id
        ]);
        $appliedToAll       = $coupon['class'] == Coupon::CLASSES['APPLIES_TO_ALL']              ?  $this->appliedToAll($coupon, $order) : 0;
        $appliedToTypes     = $coupon['class'] == Coupon::CLASSES['APPLIES_TO_SPECIFIC_TYPES']   ?  $this->appliedToTypes($coupon, $order) : 0;
        $appliedToProducts  = $coupon['class'] == Coupon::CLASSES['APPLIES_TO_SPECIFIC_PRODUCT'] ?  $this->appliedToProducts($coupon, $order) : 0;                
        
        if ($this->isApplicable($order, $customer, $coupon)) {
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

    protected function appliedToAll($coupon, $order)
    {
        $isPercentage       = $coupon->fixed_or_perc == 2 ? true : false;
        $multilineRestrict  = $coupon->multiline_restrict_plans ? $coupon->multilinePlanTypes->pluck('plan_type')->toArray() : null;
        $countItems         = 0;
        $totalDiscount      = 0;

        foreach ($order->allOrderGroup as $og) {
            // device charges
            if ($og->device_id) {
                $deviceCharge   = $og->plan_id ? $og->device->amount_w_plan : $og->device->amount;
                $totalDiscount  += $deviceCharge;
                $deviceDiscount = number_format($isPercentage ? $deviceCharge * $coupon->amount / 100 : $coupon->amount, 2);
                $orderCouponProduct[] = $deviceDiscount ? $this->orderCouponProducts(self::SPECIFIC_TYPES['DEVICE'], $og->device_id, $coupon->amount, $deviceDiscount, $og->id) : [];
                $deviceDiscount ? $countItems++ : null;
            }
            // plan charges
            if ($og->plan_id) {
                $planCharge     = $og->plan_prorated_amt ?: $og->plan->amount_recurring;
                $planDiscount   = number_format($isPercentage ? $planCharge * $coupon->amount / 100 : $coupon->amount, 2);
                if ($multilineRestrict && in_array($og->plan->type, $multilineRestrict) || !$multilineRestrict) {
                    $totalDiscount  += $planDiscount ? $planCharge : 0;
                    $orderCouponProduct[] = $planDiscount ? $this->orderCouponProducts(self::SPECIFIC_TYPES['PLAN'], $og->plan_id, $coupon->amount, $planDiscount, $og->id) : [];
                    $planDiscount ? $countItems++ : null;
                }
            }
            // sim charges
            if ($og->sim_id) {
                $simCharge      = $og->plan_id ? $og->sim->amount_w_plan : $og->sim->amount_alone; 
                $totalDiscount += $simCharge;
                $simDiscount    = number_format($isPercentage ? $simCharge * $coupon->amount / 100 : $coupon->amount, 2);
                $orderCouponProduct[] = $simDiscount ? $this->orderCouponProducts(self::SPECIFIC_TYPES['SIM'], $og->sim_id, $coupon->amount, $simDiscount, $og->id) : [];
                $simDiscount ? $countItems++ : null;
            }
            // addon charges
            if ($og->plan_id) {
                foreach ($og->addons as $addon) {
                    $addonCharge = $order->addonProRate($addon->id) ?: $addon->amount_recurring;
                    $totalDiscount += $addonCharge;
                    $addonDiscount = number_format($isPercentage ? $addonCharge * $coupon->amount / 100 : $coupon->amount, 2);
                    $orderCouponProduct[] = $addonDiscount ? $this->orderCouponProducts(self::SPECIFIC_TYPES['ADDON'], $addon->id, $coupon->amount, $addonDiscount, $og->id) : [];
                    $addonDiscount ? $countItems++ : null;
                }
            }
        }
        $total = $isPercentage ? $totalDiscount * $coupon->amount / 100 : $coupon->amount * $countItems;
        isset($orderCouponProduct) ? $this->orderCoupon($orderCouponProduct, $order) : null;
        return (['total' => number_format($total, 2), 'applied_to' => isset($orderCouponProduct) ? $orderCouponProduct : []]);
    }

    protected function appliedToTypes($couponMain, $order)
    {
        $isPercentage       = $couponMain->fixed_or_perc == 2 ? true : false;
        $multilineRestrict  = $couponMain->multiline_restrict_plans ? $couponMain->multilinePlanTypes->pluck('plan_type')->toArray() : null;
        $totalDiscount      = 0;

        foreach ($couponMain->couponProductTypes as $coupon) {
            foreach ($order->allOrderGroup as $og) {
                // For Device types
                if ($coupon->type == self::SPECIFIC_TYPES['DEVICE'] && $og->device_id) {
                    $deviceData = $this->couponForDevice($og, $isPercentage, $coupon);
                    if (!$deviceData['discount'] || $deviceData['discount'] == 0) continue;
                    $totalDiscount += $deviceData['discount'];
                    $orderCouponProduct[] = $deviceData['products'];
                }
                // For Plan types
                if ($coupon->type == self::SPECIFIC_TYPES['PLAN'] && $og->plan_id) {
                    if ($multilineRestrict && !in_array($og->plan->type, $multilineRestrict)) continue;
                    if ($coupon->sub_type && $og->plan->type != $coupon->sub_type) continue;
                    $planData = $this->couponForPlans($og, $order, $isPercentage, $coupon);
                    if ($planData['discount'] == 0 || !$planData['discount']) continue;
                    $totalDiscount += $planData['discount'];
                    $orderCouponProduct[] = $planData['products'];
                }
                // For Sim types
                if ($coupon['type'] == self::SPECIFIC_TYPES['SIM'] && $og->sim_id) {
                    $simData = $this->couponForSims($og, $isPercentage, $coupon);
                    if ($simData['discount'] == 0 || !$simData['discount']) continue;
                    $totalDiscount += $simData['discount'];
                    $orderCouponProduct[] = $simData['products'];
                }
                //For Addon types
                if ($coupon['type'] == self::SPECIFIC_TYPES['ADDON'] && $og->addons->count()) {
                    foreach ($og->addons as $addon) {
                        if ($addon->id != $coupon->product_id) continue;
                        $addonData = $this->couponForAddons($order, $addon, $isPercentage, $coupon, $og);
                        if ($addonData['discount'] == 0 || !$addonData['discount']) continue;
                        $totalDiscount += $addonData['discount'];
                        $orderCouponProduct[] = $addonData['products'];
                    }
                }
            }
        }
        isset($orderCouponProduct) ? $this->orderCoupon($orderCouponProduct, $order) : null;
        return (['total' => $totalDiscount, 'applied_to' => isset($orderCouponProduct) ? $orderCouponProduct : []]);
    }

    protected function appliedToProducts($couponMain, $order)
    {
        $isPercentage       = $couponMain['fixed_or_perc'] == 2 ? true : false;
        $multilineRestrict  = $couponMain['multiline_restrict_plans'] == 1 ? $couponMain->multilinePlanTypes->pluck('plan_type')->toArray() : null;
        $totalDiscount      = 0;

        foreach ($couponMain->couponProducts as $coupon) {
            foreach ($order->allOrderGroup as $og) {
                // For plans
                if ($coupon->product_type == self::SPECIFIC_TYPES['PLAN'] && $coupon->product_id == $og->plan_id) {
                    if ($multilineRestrict && !in_array($og->plan->type, $multilineRestrict)) continue;
                    $planData = $this->couponForPlans($og, $order, $isPercentage, $coupon);
                    if ($planData['discount'] == 0 || !$planData['discount']) continue;
                    $totalDiscount += $planData['discount'];
                    $orderCouponProduct[] = $planData['products'];
                }
                // For devices
                if ($coupon->product_type == self::SPECIFIC_TYPES['DEVICE'] && $coupon->product_id == $og->device_id) {
                    $deviceData = $this->couponForDevice($og, $isPercentage, $coupon);
                    if (!$deviceData['discount'] || $deviceData['discount'] == 0) continue;
                    $totalDiscount += $deviceData['discount'];
                    $orderCouponProduct[] = $deviceData['products'];
                }
                // For Sims
                if ($coupon->product_type == self::SPECIFIC_TYPES['SIM'] && $coupon->product_id == $og->sim_id) {
                    $simData = $this->couponForSims($og, $isPercentage, $coupon);
                    if ($simData['discount'] == 0 || !$simData['discount']) continue;
                    $totalDiscount += $simData['discount'];
                    $orderCouponProduct[] = $simData['products'];
                }
                // For Addons
                if ($coupon->product_type == self::SPECIFIC_TYPES['ADDON'] && $og->addons->count()) {
                    foreach ($og->addons as $addon) {
                        if ($addon->id != $coupon->product_id) continue;
                        $addonData = $this->couponForAddons($order, $addon, $isPercentage, $coupon, $og);
                        if ($addonData['discount'] == 0 || !$addonData['discount']) continue;
                        $totalDiscount += $addonData['discount'];
                        $orderCouponProduct[] = $addonData['products'];
                    }
                }
            }
        }
        isset($orderCouponProduct) ? $this->orderCoupon($orderCouponProduct, $order) : null;
        return (['total' => $totalDiscount, 'applied_to' => isset($orderCouponProduct) ? $orderCouponProduct : []]);
    }

    protected function orderCouponProducts($productType, $productId, $couponAmount, $discount, $orderGroupId)
    {
        return [
            'order_product_type'    => $productType,
            'order_product_id'      => $productId,
            'amount'                => $couponAmount,
            'discount'              => $discount,
            'order_group_id'        => $orderGroupId
        ];
    }

    protected function orderCoupon($data, $order)
    {
        $order->orderCoupon->orderCouponProduct()->delete();
        if (count($data) && isset($order->orderCoupon)) {
            foreach ($data as $product) {
                $order->orderCoupon->orderCouponProduct()->create([
                    'order_product_type'    => $product['order_product_type'],
                    'order_product_id'      => $product['order_product_id'],
                    'amount'                => $product['amount']
                ]);
            }
        }
    }

    protected function couponForDevice($og, $isPercentage, $coupon)
    {
        $deviceAmount = $og->plan_id ? $og->device->amount_w_plan : $og->device->amount;
        $deviceDiscount = number_format($isPercentage ? $coupon->amount * $deviceAmount / 100 : $coupon->amount, 2);
        $orderCouponProduct = $this->orderCouponProducts(self::SPECIFIC_TYPES['DEVICE'], $og->device_id, $coupon->amount, $deviceDiscount, $og->id);
        return ['discount' => $deviceDiscount, 'products' => $orderCouponProduct];
    }

    protected function couponForPlans($og, $order, $isPercentage, $coupon)
    {
        $planAmount = $order->planProRate($og->plan_id) ?: $og->plan->amount_recurring;
        $planDiscount = number_format($isPercentage ? $coupon->amount * $planAmount / 100 : $coupon->amount, 2);
        $orderCouponProduct = $this->orderCouponProducts(self::SPECIFIC_TYPES['PLAN'], $og->plan_id, $coupon->amount, $planDiscount, $og->id);
        return ['discount' => $planDiscount, 'products' => $orderCouponProduct];
    }

    protected function couponForSims($og, $isPercentage, $coupon)
    {
        $simAmount = $og->plan_id ? $og->sim->amount_w_plan : $og->sim->amount_alone;
        $simDiscount =  number_format($isPercentage ? $coupon->amount * $simAmount / 100 : $coupon->amount, 2);
        $orderCouponProduct = $this->orderCouponProducts(self::SPECIFIC_TYPES['SIM'], $og->sim_id, $coupon->amount, $simDiscount, $og->id);
        return ['discount' => $simDiscount, 'products' => $orderCouponProduct];
    }
    
    
    protected function couponForAddons($order, $addon, $isPercentage, $coupon, $og)
    {
        $addonAmount = $order->addonProRate($addon->id) ?: $addon->amount_recurring;
        $addonDiscount  = number_format($isPercentage ? $coupon->amount * $addonAmount / 100 : $coupon->amount, 2);
        $orderCouponProduct = $this->orderCouponProducts(self::SPECIFIC_TYPES['ADDON'], $addon->id, $coupon->amount, $addonDiscount, $og->id);
        return ['discount' => $addonDiscount, 'products' => $orderCouponProduct];
    }
}