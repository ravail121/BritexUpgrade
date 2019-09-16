<?php

namespace App\Http\Controllers\Api\V1\Traits;

use App\Model\Coupon;
use App\Model\CustomerCoupon;
use App\Model\InvoiceItem;

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
                        'description'     => "(Coupon) ".$couponData['code'],
                        'amount'          => number_format($couponData['amount'], 2),
                        'start_date'      => $order->invoice->start_date,
                        'taxable'         => self::TAX_FALSE,
                    ]
                );
            }
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
            $alreadyUsed = CustomerCoupon::where('coupon_id', $couponId)->where('customer_id', $order->invoice->customer_id)->count();
            if (!$alreadyUsed) {
                CustomerCoupon::create($data);
            }
        }
    }
}