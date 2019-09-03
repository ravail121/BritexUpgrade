<?php

namespace App\Http\Controllers\Api\V1\Traits;

use App\Model\Coupon;
use App\Model\CustomerCoupon;
use App\Model\InvoiceItem;

trait InvoiceCouponTrait
{
   
    public function storeCoupon($couponAmount, $couponCode, $invoice)
    {
        if ($couponCode) {
            //store coupon in invoice_items.
            if ($couponAmount) {
                $invoice->invoiceItem()->create(
                    [
                        'subscription_id' => null,
                        'product_type'    => '',
                        'product_id'      => null,
                        'type'            => InvoiceItem::TYPES['coupon'],
                        'description'     => "(Coupon) ".$couponCode,
                        'amount'          => $couponAmount,
                        'start_date'      => $invoice->start_date,
                        'taxable'         => self::TAX_FALSE,
                    ]
                );
            }

            $couponToProcess   = Coupon::where('code', $couponCode);
            $numUses           = $couponToProcess->pluck('num_uses')->first();

            $couponToProcess->update([
                'num_uses' => $numUses + 1
            ]);

            //store coupon in customer_coupon table if eligible
            $couponCycles   = $couponToProcess->first()->num_cycles;
            $couponId       = $couponToProcess->first()->id;

            $customerCoupon = [
                'customer_id'       => $invoice->customer_id,
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
                CustomerCoupon::create($data);

            } elseif ($couponCycles == 0) {
                
                $data = array_merge($customerCoupon, $customerCouponInfinite);
                CustomerCoupon::create($data);

            }
        }
    }
}