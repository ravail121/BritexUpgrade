<?php

namespace App\Http\Controllers\Api\V1\Traits;

use App\Model\Coupon;
use App\Model\CustomerCoupon;
use App\Model\InvoiceItem;
use App\Model\Order;
use App\Model\SubscriptionCoupon;
use App\Model\Addon;

trait InvoiceCouponTrait
{
    // Functions for orders
    public function storeCoupon($couponData, $order, $subscription = null)
    {
        if (isset($couponData['code'])) {
            $couponToProcess   = Coupon::where('code', $couponData['code'])->first();
            if (!$couponToProcess) { return ['error' => 'Invalid coupon code']; }
            //store coupon in invoice_items.
            if ($couponData['amount']) {
                $order->invoice->invoiceItem()->create(
                    [
                        'subscription_id' => $subscription ? $subscription->id : 0,
                        'product_type'    => $this->ifMultiline($couponToProcess) ? Coupon::TYPES['customer_coupon'] : Coupon::TYPES['subscription_coupon'],
                        'product_id'      => $couponToProcess->id,
                        'type'            => InvoiceItem::TYPES['coupon'],
                        'description'     => $couponToProcess->code. '('.$couponToProcess->name.')',
                        'amount'          => $couponData['amount'],
                        'start_date'      => $order->invoice->start_date,
                        'taxable'         => self::TAX_FALSE,
                    ]
                );
            }
        }
    }

    protected function ifMultiline($coupon)
    {
        if ($coupon->multiline_min || $coupon->multiline_max) {
            return true;
        }
        return false;
    }

    public function updateCouponNumUses($order)
    {
        $order = Order::find($order->id);
        $orderCoupon = $order->orderCoupon;
        if ($orderCoupon) {
            if ($orderCoupon->orderCouponProduct->count()) {
                $numUses = $orderCoupon->coupon->num_uses;
                $orderCoupon->coupon->update([
                    'num_uses' => $numUses + 1
                ]);
            }
            $this->insertIntoTables($order);
        }
    }

    protected function insertIntoTables($order)
    {
        $coupon        = Coupon::find($order->orderCoupon->coupon_id);
        $multiline     = $this->ifMultiline($coupon);
        if ($coupon->num_cycles != 1) {

            $data['cycles_remaining'] = $coupon->num_cycles == 0 ? -1 : $coupon->num_cycles - 1;
            $data['coupon_id']   = $order->orderCoupon->coupon_id;

            if ($multiline) {
                $data['customer_id'] = $order->invoice->customer_id;
                CustomerCoupon::create($data);
            } else {
                $subscriptionIds = $order->invoice->invoiceItem->where('type', InvoiceItem::TYPES['coupon'])->pluck('subscription_id')->toArray();
                foreach ($subscriptionIds as $id) {
                    if ($id) {
                        $data['subscription_id'] = $id;
                        SubscriptionCoupon::create($data);
                    }
                }
            }
        }
    }

    // Functions for monthly invoices
    public function customerAccountCoupons($customer, $invoice)
    {
        $customerCouponRedeemable = $customer->customerCouponRedeemable;
        if ($customerCouponRedeemable) {
            foreach ($customerCouponRedeemable as $customerCoupon) {
                $coupon = $customerCoupon->coupon;
                
                if($customerCoupon->cycles_remaining == 0) continue;

                list($isApplicable, $subscriptions) = 
                            $this->isCustomerAccountCouponApplicable(
                                $coupon,
                                $customer->billableSubscriptions
                            );
                
                if($isApplicable){
                    $coupon->load('couponProductTypes', 'couponProducts');

                    foreach($subscriptions as $subscription){

                        $amount = $this->couponAmount($subscription, $coupon);

                        // Possibility of returning 0 as well but
                        // returns false when coupon is not applicable
                        if($amount === false || $amount == 0) continue;

                        $invoice->invoiceItem()->create([
                            'subscription_id' => $subscription->id,
                            'product_type'    => '',
                            'product_id'      => $coupon->id,
                            'type'            => InvoiceItem::TYPES['coupon'],
                            'description'     => $coupon->code. '('.$coupon->name.')',
                            'amount'          => str_replace(',', '',number_format($amount, 2)),
                            'start_date'      => $invoice->start_date,
                            'taxable'         => self::TAX_FALSE,
                        ]);
                    }
                    if ($customerCoupon['cycles_remaining'] > 0) {
                        $customerCoupon->update(['cycles_remaining' => $customerCoupon['cycles_remaining'] - 1]);
                    }
                    // ToDo: Add logs,Order not provided in requirements
                }
            }
        }
    }

    protected function isCustomerAccountCouponApplicable($coupon, $subscriptions)
    {
        $isApplicable  = true;
        $multilineMin = $coupon->multiline_min;
            if($coupon->multiline_restrict_plans){
                $supportedPlanTypes = $coupon->multilinePlanTypes->pluck('plan_type');
                foreach ($subscriptions as $sub) {
                    $supportedPlanTypes->contains($sub->plan->type) ? $sub['restricted_type'] = 1 : $sub['restricted_type'] = 0;
                }
            }

        $isApplicable = $isApplicable && ($subscriptions->count() >= $multilineMin);
        if($coupon->multiline_max){
            $isApplicable = $isApplicable && $subscriptions->count() <= $coupon->multiline_max;
        }
        
        return [$isApplicable, $subscriptions];
    }

    private function couponAmount($subscription, $coupon)
    {
        if (isset($subscription->restricted_type)) {
            $plan =  $subscription->restricted_type ? $subscription->plan : null;
        } else {
            $plan = $subscription->plan;
        }
        $addons = $subscription->subscriptionAddon;
        $amount = [0];
        if($coupon->class == Coupon::CLASSES['APPLIES_TO_SPECIFIC_TYPES']){
            $planTypes  = $coupon->couponProductPlanTypes;
            $addonTypes = $coupon->couponProductAddonTypes;
            $amount[]   = $this->couponProductTypesAmount($planTypes, $plan, $coupon, $addonTypes, $addons);
        } elseif($coupon->class == Coupon::CLASSES['APPLIES_TO_SPECIFIC_PRODUCT']){
            $planProducts   = $coupon->couponPlanProducts;
            $addonProducts  = $coupon->couponAddonProducts;
            $amount[]       = $this->couponProductsAmount($planProducts, $plan, $coupon, $addonProducts, $addons); 
        } else {
            $amount[] = $this->couponAllTypesAmount($plan, $coupon, $addons);
        }

        return array_sum($amount);
    }


    public function customerSubscriptionCoupons($invoice, $subscriptions)
    {
        foreach($subscriptions as $subscription){
            
            $subscriptionCouponRedeemable = $subscription->subscriptionCouponRedeemable;

            // Subscription doesnot has any coupons
            if(!$subscriptionCouponRedeemable) continue;

            foreach ($subscriptionCouponRedeemable as $subscriptionCoupon) {
                $coupon = $subscriptionCoupon->coupon;

                if($subscriptionCoupon->cycles_remaining == 0) continue;

                $coupon->load('couponProductTypes', 'couponProducts');

                $amount = $this->couponAmount($subscription, $coupon);

                // Possibility of returning 0 as well but
                // returns false when coupon is not applicable
                if($amount === false || $amount == 0) continue;

                $invoice->invoiceItem()->create([
                    'subscription_id' => $subscription->id,
                    'product_type'    => '',
                    'product_id'      => $coupon->id,
                    'type'            => InvoiceItem::TYPES['coupon'],
                    'description'     => $coupon->code. '('.$coupon->name.')',
                    'amount'          => str_replace(',', '', number_format($amount, 2)),
                    'start_date'      => $invoice->start_date,
                    'taxable'         => self::TAX_FALSE,
                ]);

                if ($subscriptionCoupon['cycles_remaining'] > 0) {
                    $subscriptionCoupon->update(['cycles_remaining' => $subscriptionCoupon['cycles_remaining'] - 1]);
                }
            }

        }

    }
    
    protected function couponProductTypesAmount($planTypes, $plan, $coupon, $addonTypes, $addons)
    {
        $amount = [0];
        $isPercentage = $coupon->fixed_or_perc == Coupon::FIXED_PERC_TYPES['percentage'] ? true : false;
        foreach ($planTypes as $planType) {
          
            if ($plan) {
                if ($planType->sub_type != 0) {
                    if ($planType->sub_type == $plan->type) {
                        $amount[] = $isPercentage ? $planType->amount * $plan->amount_recurring / 100 : $planType->amount;
                    }
                } else {
                    
                    $amount[] = $isPercentage ? $planType->amount * $plan->amount_recurring / 100 : $planType->amount;
              
                }
            }
        }

        foreach ($addonTypes as $addonType) {
            foreach ($addons as $addon) {
                if ($addon->addon_id) {
                    $addonAmount = Addon::find($addon->addon_id)->amount_recurring;
                    if ($addonType->sub_type != 0) {
                        if ($addonType->sub_type == $plan->type) {
                            $amount[] = $isPercentage ? $addonType->amount * $addonAmount / 100 : $addonType->amount;
                        }
                    } else {
                        $amount[] = $isPercentage ? $addonType->amount * $addonAmount / 100 : $addonType->amount;
                    }
                }
            }
        }
        return array_sum($amount);
    }

    protected function couponProductsAmount($planProducts, $plan, $coupon, $addonProducts, $addons)
    {
        $amount = [0];
        $isPercentage = $coupon->fixed_or_perc == Coupon::FIXED_PERC_TYPES['percentage'] ? true : false;
        foreach ($planProducts as $product) {
            if ($plan) {

                if ($product->product_id == $plan->id) {

                    $amount[] = $isPercentage ? $product->amount * $plan->amount_recurring / 100 : $product->amount;
                }
            }
        }
        foreach ($addonProducts as $product) {
            foreach ($addons as $addon) {
                if ($addon->addon_id) {
                    $addonAmount = Addon::find($addon->addon_id)->amount_recurring;
                    $amount[] = $isPercentage ? $product->amount * $addonAmount / 100 : $product->amount;
                }
            }
        }

        return array_sum($amount);
    }

    public function couponAllTypesAmount($plan, $coupon, $addons)
    {
        $amount = [0];
        $isPercentage = $coupon->fixed_or_perc == Coupon::FIXED_PERC_TYPES['percentage'] ? true : false;
        if ($plan) {
            $amount[]     = $isPercentage ? $coupon->amount * $plan->amount_recurring / 100 : $coupon->amount;
        }
        foreach ($addons as $addon) {
            if ($addon->addon_id) {
                $addonAmount = Addon::find($addon->addon_id)->amount_recurring;
                $amount[] = $isPercentage ? $coupon->amount * $addonAmount / 100 : $coupon->amount;
            }
        }
        return array_sum($amount);
    }

}