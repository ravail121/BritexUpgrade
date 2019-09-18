<?php

namespace App\Http\Controllers\Api\V1\Traits;

use App\Model\Coupon;
use App\Model\CustomerCoupon;
use App\Model\InvoiceItem;
use App\Model\SubscriptionCoupon;

trait InvoiceCouponTrait
{
   
    public function storeCoupon($couponData, $order, $subscription = null)
    {
        if (isset($couponData['code'])) {
            $data = [];
            $couponToProcess   = Coupon::where('code', $couponData['code']);
            //store coupon in invoice_items.
            if ($couponData['amount']) {
                $order->invoice->invoiceItem()->create(
                    [
                        'subscription_id' => $subscription ? $subscription->id : null,
                        'product_type'    => '',
                        'product_id'      => $couponToProcess->first()->id,
                        'type'            => InvoiceItem::TYPES['coupon'],
                        'description'     => $couponData['description'],
                        'amount'          => $couponData['amount'],
                        'start_date'      => $order->invoice->start_date,
                        'taxable'         => self::TAX_FALSE,
                    ]
                );
            }
            $numUses       = $couponToProcess->pluck('num_uses')->first();
            $couponCycles  = $couponToProcess->first()->num_cycles;
            $couponId      = $couponToProcess->first()->id;

            $couponToProcess->update([
                'num_uses' => $numUses + $order->orderCoupon->orderCouponProduct->count()
            ]);

            $data = $this->infiniteOrNot($couponCycles);
            $this->insertIntoTables($couponId, $order, $subscription, $data);
        }
    }

    protected function infiniteOrNot($couponCycles)
    {

        $customerCouponInfinite = [
            'cycles_remaining'  => -1
        ];

        $customerCouponFinite = [
            'cycles_remaining'  => $couponCycles - 1
        ];

        if ($couponCycles > 0) {
            $data = $customerCouponFinite;
        } elseif ($couponCycles == 0) {
            $data = $customerCouponInfinite;
        }
        return $data;
    }

    protected function insertIntoTables($couponId, $order, $subscription, $data)
    {
        $alreadyUsedAccountCoupon = CustomerCoupon::where('coupon_id', $couponId)->where('customer_id', $order->invoice->customer_id)->count();
        $alreadyUsedSubscriptionCoupon = SubscriptionCoupon::where('coupon_id', $couponId)->where('subscription_id', $order->invoice->customer_id)->count();
        if (!$alreadyUsedAccountCoupon && !$subscription) {
            $data['customer_id'] = $order->invoice->customer_id;
            $data['coupon_id']   = $couponId;
            CustomerCoupon::create($data);
        }
        if (!$alreadyUsedSubscriptionCoupon && $subscription) {
            $data['subscription_id'] = $subscription->id;
            $data['coupon_id']   = $couponId;
            SubscriptionCoupon::create($data);
        }
    }
}