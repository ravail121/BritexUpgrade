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
            $couponCycles  = $couponToProcess->first()->num_cycles;
            $couponId      = $couponToProcess->first()->id;
            $data = $this->infiniteOrNot($couponCycles);
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

    protected function updateCouponNumUses($order)
    {
        $orderCoupon = $order->orderCoupon;
        if (isset($orderCoupon) && $orderCoupon->count()) {
            $numUses = $orderCoupon->coupon->num_uses;
            $orderCoupon->coupon->update([
                'num_uses' => $numUses + 1
            ]);
            $this->insertIntoTables($order);
        }
    }

    protected function insertIntoTables($order)
    {
        $coupon        = Coupon::find($order->orderCoupon->coupon_id);
        if ($coupon->num_cycles != 1) {
            $data['cycles_remaining'] = $coupon->num_cycles == 0 ? -1 : $coupon->num_cycles - 1;
            $data['customer_id'] = $order->invoice->customer_id;
            $data['coupon_id']   = $order->orderCoupon->coupon_id;
            CustomerCoupon::create($data);
        }

    }
}