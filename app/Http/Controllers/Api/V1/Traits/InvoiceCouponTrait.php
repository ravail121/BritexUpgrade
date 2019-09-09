<?php

namespace App\Http\Controllers\Api\V1\Traits;

use App\Model\Coupon;
use App\Model\CustomerCoupon;
use App\Model\InvoiceItem;
use App\Model\OrderCoupon;
use App\Model\OrderCouponProduct;

trait InvoiceCouponTrait
{
   
    public function storeCoupon($couponData, $order)
    {
        if (isset($couponData['code'])) {
            //store coupon in invoice_items.
            if ($couponData['amount']) {
                $order->invoice->invoiceItem()->create(
                    [
                        'subscription_id' => null,
                        'product_type'    => '',
                        'product_id'      => null,
                        'type'            => InvoiceItem::TYPES['coupon'],
                        'description'     => "(Coupon) ".$couponData['code'],
                        'amount'          => $couponData['amount'],
                        'start_date'      => $order->invoice->start_date,
                        'taxable'         => self::TAX_FALSE,
                    ]
                );
            }

            $couponToProcess   = Coupon::where('code', $couponData['code']);
            $numUses           = $couponToProcess->pluck('num_uses')->first();

            $couponToProcess->update([
                'num_uses' => $numUses + $order->orderCoupon->orderCouponProduct->count()
            ]);

            //store coupon in customer_coupon table if eligible
            $couponCycles   = $couponToProcess->first()->num_cycles;
            $couponId       = $couponToProcess->first()->id;

            $customerCoupon = [
                'customer_id'       => $order->invoice->customer_id,
                'coupon_id'         => $couponId,
            ];

            $customerCouponInfinite = [
                'cycles_remaining'  => -1
            ];

            $customerCouponFinite = [
                'cycles_remaining'  => $couponCycles - 1
            ];

            if ($couponCycles > 0) {

                $data = array_merge($customerCoupon, $customerCouponFinite);

            } elseif ($couponCycles == 0) {
                
                $data = array_merge($customerCoupon, $customerCouponInfinite);

            }
            CustomerCoupon::create($data);
        }
    }
}