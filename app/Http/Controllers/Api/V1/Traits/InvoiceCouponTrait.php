<?php

namespace App\Http\Controllers\Api\V1\Traits;

use App\Model\Coupon;
use App\Model\CustomerCoupon;
use App\Model\InvoiceItem;
use App\Model\Order;
use App\Model\SubscriptionCoupon;

trait InvoiceCouponTrait
{
   
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
}