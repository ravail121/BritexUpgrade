<?php

namespace App\Http\Controllers\Api\V1\Traits;

use App\Model\Coupon;
use App\Model\CustomerCoupon;
use App\Model\InvoiceItem;
use App\Model\Order;
use App\Model\SubscriptionCoupon;
use App\Model\Addon;
use App\Model\Plan;

trait InvoiceCouponTrait
{

	/**
	 * @var array
	 */
    protected $taxDiscount = [];

	/**
	 * Functions for orders
	 * @param      $couponData
	 * @param      $order
	 * @param null $subscription
	 *
	 * @return string[]
	 */
    public function storeCoupon($couponData, $order, $subscription = null)
    {
        if (isset($couponData['code'])) {
	        $couponsToProcess = explode(', ', $couponData['code']);
	        foreach ($couponsToProcess as $couponToProcess) {
		        $couponToProcess = Coupon::where( 'code', trim($couponToProcess) )->first();
		        if ( ! $couponToProcess ) {
			        return [ 'error' => 'Invalid coupon code' ];
		        }
		        /**
		         * store coupon in invoice_items.
		         */
		        if ($couponData['amount']) {
			        $order->invoice->invoiceItem()->create(
				        [
					        'subscription_id' => $subscription ? $subscription->id : 0,
					        'product_type'    => $this->ifMultiline($couponToProcess) ? Coupon::TYPES['customer_coupon'] : Coupon::TYPES['subscription_coupon'],
					        'product_id'      => $couponToProcess->id,
					        'type'            => InvoiceItem::TYPES['coupon'],
					        'description'     => $couponToProcess->code,
					        'amount'          => $couponData['amount'],
					        'start_date'      => $order->invoice->start_date,
					        'taxable'         => false,
				        ]
			        );
		        }
	        }
        }
    }

	/**
	 * @param $coupon
	 *
	 * @return bool
	 */
    protected function ifMultiline($coupon)
    {
        if ($coupon->multiline_min || $coupon->multiline_max) {
            return true;
        }
        return false;
    }

	/**
	 * @param $order
	 */
    public function updateCouponNumUses($order)
    {
        $order = Order::find($order->id);
        $orderCoupons = $order->orderCoupon;
        if ($orderCoupons) {
        	foreach($orderCoupons as $orderCoupon){
		        if ($orderCoupon->orderCouponProduct->count()) {
			        $numUses = $orderCoupon->coupon->num_uses;
			        $orderCoupon->coupon->update([
				        'num_uses' => $numUses + 1
			        ]);
		        }
		        $this->insertIntoTables($orderCoupon->coupon, $order->customer_id, $order->subscriptions->pluck('id')->toArray());
	        }
            return;
        }
    }

	/**
	 * @param       $coupon
	 * @param       $customerId
	 * @param       $subscriptionIds
	 * @param false $admin
	 *
	 * @return array|null
	 */
    public function insertIntoTables($coupon, $customerId, $subscriptionIds, $admin = false)
    {
        $multiline     = $this->ifMultiline($coupon);
        $numCycles = $admin ? $coupon->num_cycles : $coupon->num_cycles - 1;
        $data['cycles_remaining'] = $coupon->num_cycles == 0 ? -1 : $numCycles;
        $data['coupon_id']   = $coupon->id;
        $coupon->increment('num_uses');
        $response = null;
        if ($multiline) {
            $data['customer_id'] = $customerId;
            $couponAdded = CustomerCoupon::create($data);
            $response = ['success' => 'Coupon added', 'id' => $couponAdded->id];
        } else {
            foreach ($subscriptionIds as $id) {
                $data['subscription_id'] = $id;
                $couponAdded = SubscriptionCoupon::create($data);
                $response = ['success' => 'Coupon added', 'id' => $couponAdded->id];
            }
        }
        return $response;
    }


	/**
	 * Functions for monthly invoices
	 * @param $customer
	 * @param $invoice
	 */
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
                            'product_type'    => 'Customer Coupon',
                            'product_id'      => $customerCoupon->id,
                            'type'            => InvoiceItem::TYPES['coupon'],
                            'description'     => $coupon->code,
                            'amount'          => str_replace(',', '',number_format($amount, 2)),
                            'start_date'      => $invoice->start_date,
                            'taxable'         => false,
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

	/**
	 * @param $coupon
	 * @param $subscriptions
	 *
	 * @return array
	 */
    protected function isCustomerAccountCouponApplicable($coupon, $subscriptions)
    {
        $isApplicable  = true;
        $multilineMin = $coupon->multiline_min;
        $isApplicable = $isApplicable && ($subscriptions->count() >= $multilineMin);
        if($coupon->multiline_max){
            $isApplicable = $isApplicable && $subscriptions->count() <= $coupon->multiline_max;
        }
        
        return [$isApplicable, $subscriptions];
    }

	/**
	 * @param $subscription
	 * @param $coupon
	 *
	 * @return float|int
	 */
    private function couponAmount($subscription, $coupon)
    {
        $amount = [0];
        if ($coupon->multiline_restrict_plans) {
            $supportedPlanTypes = $coupon->multilinePlanTypes->pluck('plan_type');
            if (!$supportedPlanTypes->contains($subscription->plan->type)) {
                return array_sum($amount);
            }
        } 
        $plan = $subscription->plan;
        $addons = $subscription->subscriptionAddon;
        $tax = isset($subscription->customerRelation->stateTax->rate) ? $subscription->customerRelation->stateTax->rate : 0;
        if($coupon->class == Coupon::CLASSES['APPLIES_TO_SPECIFIC_TYPES']){
            $planTypes  = $coupon->couponProductPlanTypes;
            $addonTypes = $coupon->couponProductAddonTypes;
            $amount[]   = $this->couponProductTypesAmount($planTypes, $plan, $coupon, $addonTypes, $addons, $tax, $subscription);
        } elseif($coupon->class == Coupon::CLASSES['APPLIES_TO_SPECIFIC_PRODUCT']){
            $planProducts   = $coupon->couponPlanProducts;
            $addonProducts  = $coupon->couponAddonProducts;
            $amount[]       = $this->couponProductsAmount($planProducts, $plan, $coupon, $addonProducts, $addons, $tax, $subscription); 
        } else {
            $amount[] = $this->couponAllTypesAmount($plan, $coupon, $addons, $tax, $subscription);
        }

        return array_sum($amount);
    }

	/**
	 * @param $invoice
	 * @param $subscriptions
	 */
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
                    'product_type'    => 'Subscription Coupon',
                    'product_id'      => $subscriptionCoupon->id,
                    'type'            => InvoiceItem::TYPES['coupon'],
                    'description'     => $coupon->code,
                    'amount'          => str_replace(',', '', number_format($amount, 2)),
                    'start_date'      => $invoice->start_date,
                    'taxable'         => false,
                ]);

                if ($subscriptionCoupon['cycles_remaining'] > 0) {
                    $subscriptionCoupon->decrement('cycles_remaining');
                }
            }
        }
    }

	/**
	 * @param $planTypes
	 * @param $plan
	 * @param $coupon
	 * @param $addonTypes
	 * @param $addons
	 * @param $tax
	 * @param $subscription
	 *
	 * @return float|int
	 */
    protected function couponProductTypesAmount($planTypes, $plan, $coupon, $addonTypes, $addons, $tax, $subscription)
    {
        $amount = [0];
        $isPercentage = $coupon->fixed_or_perc == Coupon::FIXED_PERC_TYPES['percentage'] ? true : false;
        foreach ($planTypes as $planType) {
            if ($plan) {
                if ($planType->sub_type != 0 && $planType->sub_type == $plan->type || $planType->sub_type == 0) {
                    $discount = $isPercentage ? $planType->amount * $plan->amount_recurring / 100 : $planType->amount;
                    $amount[] = $discount;
                    if ($plan->taxable) {
                        $this->taxDiscount[$subscription->id][] = $discount;
                    }
                }
            }
        }

        foreach ($addonTypes as $addonType) {
            foreach ($addons as $a) {
                $addon = $a->addon;
                $addonAmount = $addon->amount_recurring;
                $discount = $isPercentage ? $addonType->amount * $addonAmount / 100 : $addonType->amount;
                $amount[] = $discount;
                if ($addon->taxable) {
                    $this->taxDiscount[$subscription->id][] = $discount;
                }
            }
        }
        return array_sum($amount);
    }

	/**
	 * @param $planProducts
	 * @param $plan
	 * @param $coupon
	 * @param $addonProducts
	 * @param $addons
	 * @param $tax
	 * @param $subscription
	 *
	 * @return float|int
	 */
    protected function couponProductsAmount($planProducts, $plan, $coupon, $addonProducts, $addons, $tax, $subscription)
    {
        $amount = [0];
        $isPercentage = $coupon->fixed_or_perc == Coupon::FIXED_PERC_TYPES['percentage'] ? true : false;
        foreach ($planProducts as $product) {
            if ($plan) {
                if ($product->product_id == $plan->id) {
                    $discount = $isPercentage ? $product->amount * $plan->amount_recurring / 100 : $product->amount;
                    $amount[] = $discount;
                    if ($plan->taxable) {
                        $this->taxDiscount[$subscription->id][] = $discount;
                    }
                }
            }
        }
        foreach ($addonProducts as $product) {
            foreach ($addons as $a) {
                if ($a->addon_id == $product->product_id) {
                    $addon = $a->addon;
                    $addonAmount = $addon->amount_recurring;
                    $discount = $isPercentage ? $product->amount * $addonAmount / 100 : $product->amount;
                    $amount[] = $discount;
                    if ($addon->taxable) {
                        $this->taxDiscount[$subscription->id][] = $discount;
                    }
                }
            }
        }
        return array_sum($amount);
    }

	/**
	 * @param $plan
	 * @param $coupon
	 * @param $addons
	 * @param $tax
	 * @param $subscription
	 *
	 * @return float|int
	 */
    public function couponAllTypesAmount($plan, $coupon, $addons, $tax, $subscription)
    {
        $amount = [0];
        $products = [];
        $isPercentage = $coupon->fixed_or_perc == Coupon::FIXED_PERC_TYPES['percentage'] ? true : false;
        if ($plan) {
            $discount = $isPercentage ? $coupon->amount * $plan->amount_recurring / 100 : $coupon->amount;
            $amount[] = $discount;
            if ($plan->taxable) {
                $this->taxDiscount[$subscription->id][] = $discount;
            }
        }
        foreach ($addons as $addon) {
            if ($addon->addon_id) {
                $addonData = Addon::find($addon->addon_id);
                $discount = $isPercentage ? $coupon->amount * $addonData->amount_recurring / 100 : $coupon->amount;
                $amount[] = $discount;
                if ($addonData->taxable) {
                    $this->taxDiscount[$subscription->id][] = $discount;
                }
            }
        }
        return array_sum($amount);
    }
   
}