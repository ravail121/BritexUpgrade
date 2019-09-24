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
            //store coupon in invoice_items.
            if ($couponData['amount']) {
                $order->invoice->invoiceItem()->create(
                    [
                        'subscription_id' => $subscription ? $subscription->id : 0,
                        'product_type'    => '',
                        'product_id'      => $couponToProcess->id,
                        'type'            => InvoiceItem::TYPES['coupon'],
                        'description'     => $couponData['description'],
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
                if ($addonType->sub_type != 0) {
                    if ($addonType->sub_type == $plan->type) {
                        $amount[] = $isPercentage ? $addonType->amount * $addon->amount_recurring / 100 : $addonType->amount;
                    }
                } else {
                    $amount[] = $isPercentage ? $addonType->amount * $addon->amount_recurring / 100 : $addonType->amount;
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
                $amount[] = $isPercentage ? $product->amount * $addon->amount_recurring / 100 : $product->amount;
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
            $amount[] = $isPercentage ? $coupon->amount * $addon->amount_recurring / 100 : $coupon->amount;
        }
        return array_sum($amount);
    }

}