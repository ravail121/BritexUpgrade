<?php

namespace App\Http\Controllers\Api\V1\Traits;

use App\Model\Customer;
use App\Model\Invoice;
use App\Model\Order;
use App\Model\Addon;
use App\Model\Coupon;
use App\Model\InvoiceItem;
use App\Model\CustomerCoupon;
use App\Model\OrderCoupon;
use App\Model\Plan;
use App\Model\SubscriptionCoupon;
use App\Model\Tax;
use Carbon\Carbon;

/**
 * Trait InvoiceCouponTrait
 *
 * @package App\Http\Controllers\Api\V1\Traits
 */
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
        if($couponData){
        	foreach($couponData as $coupon) {
		        $couponToProcess = Coupon::where( 'code', $coupon['code'] )->first();
		        if ( ! $couponToProcess ) {
			        return [ 'error' => 'Invalid coupon code' ];
		        }
		        /**
		         * store coupon in invoice_items.
		         */
		        if ( $coupon[ 'amount' ] ) {
			        $order->invoice->invoiceItem()->create(
				        [
					        'subscription_id' => $subscription ? $subscription->id : 0,
					        'product_type'    => $this->ifMultiline( $couponToProcess ) ? Coupon::TYPES[ 'customer_coupon' ] : Coupon::TYPES[ 'subscription_coupon' ],
					        'product_id'      => $couponToProcess->id,
					        'type'            => InvoiceItem::TYPES[ 'coupon' ],
					        'description'     => $couponToProcess->code,
					        'amount'          => $coupon[ 'amount' ],
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

    /**
     * @param $order_id
     * @param $coupon
     *
     * @return array
     */
    public function ifAddedByCustomerFunction($order_id, $coupon)
    {
        $order  = Order::find($order_id);
        $customer = Customer::find($order->customer_id);
        $couponEligibleFor = $this->checkEligibleProducts($coupon);

        $stateTax = isset($customer->stateTax->rate) ? $customer->stateTax->rate : 0;
        $appliedToAll       = $coupon['class'] == Coupon::CLASSES['APPLIES_TO_ALL']              ?  $this->appliedToAll($coupon, $order, $stateTax) : 0;
        $appliedToTypes     = $coupon['class'] == Coupon::CLASSES['APPLIES_TO_SPECIFIC_TYPES']   ?  $this->appliedToTypes($coupon, $order, $stateTax) : 0;
        $appliedToProducts  = $coupon['class'] == Coupon::CLASSES['APPLIES_TO_SPECIFIC_PRODUCT'] ?  $this->appliedToProducts($coupon, $order, $stateTax) : 0;

        if ($this->isApplicable($order, $customer, $coupon)) {
            OrderCoupon::updateOrCreate([
                'order_id'      => $order->id,
                'coupon_id'     => $coupon->id
            ]);
            $this->updateCouponNumUses($order);
            $total = $appliedToAll['total'] + $appliedToTypes['total'] + $appliedToProducts['total'];

            return [
                'total'                 => $total,
                'code'                  => $coupon->code,
                'coupon_type'           => $coupon->class,
                'percentage'            => $coupon->fixed_or_perc == self::FIXED_PERC_TYPES['percentage'],
                'applied_to'            => [
                    'applied_to_all'        => $appliedToAll['applied_to'],
                    'applied_to_types'      => $appliedToTypes['applied_to'],
                    'applied_to_products'   => $appliedToProducts['applied_to'],
                ],
                'coupon_amount_details' => $couponEligibleFor,
                'coupon_tax'            => array_sum($this->totalTaxableAmount) * $stateTax / 100,
                'is_stackable'          => $coupon->stackable
            ];
        } else {
            return [
                'error'     =>  $this->failedResponse
            ];
        }
    }

    /**
     * Calculates the Total Price of Cart
     *
     * @return float  $totalPrice
     */
    public function totalPrice()
    {
        if ($this->total_price) {
            $this->total_price = 0;
        }
        $this->calDevicePrices();
        $this->getPlanPrices();
        $this->getSimPrices();
        $this->getAddonPrices();
        $this->calTaxes();
        $this->getShippingFee();
        $this->calRegulatory();
        $price[] = ($this->prices) ? array_sum($this->prices) : 0;
        $price[] = ($this->regulatory) ? array_sum($this->regulatory) : 0;
        $price[] = ($this->coupon());

        if ($this->tax_total === 0) {
            $price[] = ($this->taxes) ? number_format(array_sum($this->taxes), 2) : 0;
        } else {
            $price[] = number_format($this->tax_total, 2);
        }
        $price[] = ($this->activation) ? array_sum($this->activation) : 0;
        $price[] = ($this->shippingFee) ? array_sum($this->shippingFee) : 0;
        $totalPrice = array_sum($price);
        $this->total_price = $totalPrice;
        return $totalPrice;
    }


    /**
     * It returns the array of Device-prices from an array
     *
     * @param array $type
     * @return  array
     */
    protected function calDevicePrices()
    {
        $this->prices = [];
        $activeGroupId = $this->getActiveGroupId();
        if ($this->cartItems != null) {
            if (count($this->cartItems['order_groups'])) {
                foreach ($this->cartItems['order_groups'] as $cart) {
                    if ($cart['device'] != null) {
                        if ($cart['plan'] == null) {
                            if ($cart['id'] == $activeGroupId) {
                                $this->prices[] = $cart['device']['amount_w_plan'];
                            } else {
                                $this->prices[] = $cart['device']['amount'];
                            }
                        } else {
                            $this->prices[] = $cart['device']['amount_w_plan'];
                        }
                    }
                }
            }
        }
        return true;
    }


    /**
     * Returns the active-group-id
     *
     * @return int
     */
    public function getActiveGroupId()
    {
        return (isset($this->cartItems['active_group_id'])) ? $this->cartItems['active_group_id'] : null;
    }


    /**
     * It returns the array of Plan-prices from an array
     *
     * @param array $type
     * @return  array
     */
    protected function getPlanPrices()
    {
        $this->activation = [];
        if ($this->cartItems != null) {
            if (count($this->cartItems['order_groups'])) {
                foreach ($this->cartItems['order_groups'] as $cart) {
                    if ($cart['plan']['amount_onetime'] > 0) {
                        $this->activation[] = $cart['plan']['amount_onetime'];
                    }
                    if ($cart['plan_prorated_amt']) {
                        $this->prices[] = $cart['plan_prorated_amt'];
                    } else {
                        $this->prices[] = ($cart['plan'] != null) ? $cart['plan']['amount_recurring'] : [];
                    }
                }
            }
        }
        return true;
    }


    /**
     * It returns the array of Sim-prices from an array
     *
     * @param array $type
     * @return  array
     */
    protected function getSimPrices()
    {
        if ($this->cartItems != null) {
            if (count($this->cartItems['order_groups'])) {
                foreach ($this->cartItems['order_groups'] as $cart) {
                    if ($cart['sim'] != null && $cart['plan'] != null) {
                        $this->prices[] = $cart['sim']['amount_w_plan'];
                    } elseif ($cart['sim'] != null && $cart['plan'] == null) {
                        $this->prices[] = $cart['sim']['amount_alone'];
                    }
                }
            }
        }
        return true;
    }

    /**
     * It returns the array of Addon-prices from an array
     *
     * @param array $type
     * @return  array
     */
    protected function getAddonPrices()
    {
        if ($this->cartItems != null) {
            if (count($this->cartItems['order_groups'])) {
                foreach ($this->cartItems['order_groups'] as $cart) {
                    if ($cart['addons'] != null) {
                        foreach ($cart['addons'] as $addon) {
                            if ($addon['prorated_amt'] != null) {
                                $this->prices[] = $addon['prorated_amt'];
                            } else {
                                $this->prices[] = $addon['amount_recurring'];
                            }
                        }
                    }
                }
            }
        }
        return true;
    }


    /**
     * @param null $taxId
     *
     * @return float|int
     */
    public function calTaxes($taxId = null)
    {
        $this->taxes = [];
        if ($this->cartItems != null) {
            if (count($this->cartItems['order_groups'])) {
                foreach ($this->cartItems['order_groups'] as $cart) {
                    $this->taxes[] = number_format($this->calTaxableItems($cart, $taxId), 2);
                }
            }
        }
        $taxes = ($this->taxes) ? array_sum($this->taxes) : 0;
        $taxId ?  $this->tax_total = $taxes : $this->tax_total = 0;
        $taxId ? $this->totalPrice() : null; // to add tax to total without refresh
        return $taxes;
    }


    /**
     * Calculates the Sub-Total Price of Cart
     *
     * @return float  $subtotalPrice
     */
    public function subTotalPrice()
    {
        $this->calDevicePrices();
        $this->getPlanPrices();
        $this->getSimPrices();
        $this->getAddonPrices();
        $price[] = ($this->prices) ? array_sum($this->prices) : 0;
        $price[] = ($this->activation) ? array_sum($this->activation) : 0;
        $this->subTotalPriceAmount = array_sum($price);
        return $this->subTotalPriceAmount;
    }


    /**
     * Calculates the monthly charge of Cart (plans + addons)
     *
     * @return float  $monthlyCharge
     */
    public function calMonthlyCharge()
    {
        $this->prices = [];
        $this->getOriginalPlanPrice();
        $this->getOriginalAddonPrice();
        $price = ($this->prices) ? array_sum($this->prices) : 0;
        if (isset($this->cart['paid_invoice'])) {
            $price /= 2;
        }
        return $price;
    }


    /**
     * @return float|int
     */
    public function calRegulatory()
    {
        $this->regulatory = [];
        if ($this->cartItems != null) {
            if (count($this->cartItems['order_groups'])) {
                foreach ($this->cartItems['order_groups'] as $cart) {
                    if ($cart['plan'] != null && !isset($cart['status'])) {
                        if ($cart['plan']['regulatory_fee_type'] == 1) {
                            $this->regulatory[] = $cart['plan']['regulatory_fee_amount'];
                        } elseif ($cart['plan']['regulatory_fee_type'] == 2) {
                            if ($cart['plan_prorated_amt'] != null) {
                                $this->regulatory[] = number_format($cart['plan']['regulatory_fee_amount'] * $cart['plan_prorated_amt'] / 100, 2);
                            } else {
                                $this->regulatory[] = number_format($cart['plan']['regulatory_fee_amount'] * $cart['plan']['amount_recurring'] / 100, 2);
                            }
                        }
                    }
                }
            }
        }
        $regulatory = ($this->regulatory) ? array_sum($this->regulatory) : 0;
        return $regulatory;
    }

    /**
     * @return int
     */

    public function coupon()
    {
        $couponAmountTotal = 0;
        if($this->couponAmount){
            foreach($this->couponAmount as $coupon){
                if(isset($coupon['total'])){
                    $couponAmountTotal -= (float) $coupon['total'];
                }
            }
        }
        return $couponAmountTotal;
    }

    /**
     * Gets Shipping fee
     *
     * @return float  $shippingFee
     */
    public function getShippingFee()
    {
        $this->shippingFee = [];
        if ($this->cartItems != null) {
            if (count($this->cartItems['order_groups'])) {
                foreach ($this->cartItems['order_groups'] as $cart) {
                    if ($cart['device'] != null) {
                        if ($cart['device']['shipping_fee'] != null) {
                            $this->shippingFee[] = $cart['device']['shipping_fee'];
                        }
                    }
                    if ($cart['sim'] != null) {
                        if ($cart['sim']['shipping_fee'] != null) {
                            $this->shippingFee[] = $cart['sim']['shipping_fee'];
                        }
                    }
                }
            }
        }
        $shippingFee = ($this->shippingFee) ? array_sum($this->shippingFee) : 0;
        return $shippingFee;
    }

    /**
     * @param $cart
     * @param $taxId
     *
     * @return float|int
     */
    public function calTaxableItems($cart, $taxId)
    {
        $_tax_id = null;
        $order = Order::where('hash', $this->order_hash)->first();
        $customer = Customer::find($order->customer_id);
        if (!$customer) {
            if ($this->cartItems['business_verification'] && isset($this->cartItems['business_verification']['billing_state_id'])) {
                $_tax_id = $this->cartItems['business_verification']['billing_state_id'];
            } elseif ($this->cart['customer'] && isset($this->cart['customer']['billing_state_id'])) {
                $_tax_id = $this->cartItems['customer']['billing_state_id'];
            }
        } else {
            $_tax_id = $customer->billing_state_id;
        }
        $stateId = ['tax_id' => $_tax_id];
        $taxRate    = $this->taxrate($stateId);
        $this->taxrate = isset($taxRate['tax_rate']) ? $taxRate['tax_rate'] : 0;
        $taxPercentage  = $this->taxrate / 100;
        if(isset($cart['status']) && $cart['status'] == "SamePlan"){
            $addons = $this->addTaxesToAddons($cart, $taxPercentage);
            return $addons;
        }
        if(isset($cart['status']) && $cart['status'] == "Upgrade"){
            $plans =$this->addTaxesToPlans($cart, $cart['plan'], $taxPercentage);
            $addons = $this->addTaxesToAddons($cart, $taxPercentage);
            return $plans + $addons;
        }
        $devices        = $this->addTaxesDevices($cart, $cart['device'], $taxPercentage);
        $sims           = $this->addTaxesSims($cart, $cart['sim'], $taxPercentage);
        $plans          = $this->addTaxesToPlans($cart, $cart['plan'], $taxPercentage);
        $addons         = $this->addTaxesToAddons($cart, $taxPercentage);
        return $devices + $sims + $plans + $addons;
    }


    /**
     * @param $stateId
     *
     * @return \App\Support\Utilities\Collection
     */
    public function taxrate($stateId)
    {
        $company = \Request::get('company')->load('carrier');
        if (array_key_exists('tax_id', $stateId)) {
            $rate = Tax::where('state', $stateId['tax_id'])
                ->where('company_id', $company->id)
                ->pluck('rate')
                ->first();
            return ['tax_rate' => $rate];
        }
        $msg = $this->respond(['error' => 'Hash is required']);
        if (array_key_exists('hash', $stateId)) {
            $customer = Customer::where(['hash' => $stateId['hash']])->first();
            if ($customer) {
                if (array_key_exists("paid_monthly_invoice", $stateId)) {
                    $date = Carbon::today()->addDays(6)->endOfDay();
                    $invoice = Invoice::where([
                        ['customer_id', $customer->id],
                        ['status', Invoice::INVOICESTATUS['closed&paid']],
                        ['type', Invoice::TYPES['monthly']]
                    ])->whereBetween('start_date', [Carbon::today()->startOfDay(), $date])->where('start_date', '!=', Carbon::today())->first();

                    $customer['paid_monthly_invoice'] = $invoice ? 1 : 0;
                }
                $customer['company'] = $company;
                $msg = $this->respond($customer);
            } else {
                $msg = $this->respond(['error' => 'customer not found']);

            }
        }
        return $msg;
    }

    /**
     * @param $cart
     * @param $taxId
     *
     * @return float|int
     */

    public function addTaxesToPlans($cart, $item, $taxPercentage)
    {
        $planTax = [];
        if ($item != null && $item['taxable']) {
            $amount = $cart['plan_prorated_amt'] != null ? $cart['plan_prorated_amt'] : $item['amount_recurring'];
            $amount = $item['amount_onetime'] != null ? $amount + $item['amount_onetime'] : $amount;
            if ($this->couponAmount) {
                $discounted = $this->getCouponPrice($this->couponAmount, $item, 1);
                $amount = $discounted > 0 && $amount > $discounted ? $amount - $discounted : 0;
            }
            $planTax[] = $taxPercentage * $amount;
        }
        return !empty($planTax) ? array_sum($planTax) : 0;
    }

    /**
     * @param $couponData
     * @param $item
     * @param $itemType
     *
     * @return int|mixed
     */
    protected function getCouponPrice($couponData, $item, $itemType)
    {
        $productDiscount = 0;

        foreach ($couponData as $coupon) {
        	$type = array_key_exists('coupon_type', $coupon) ? $coupon['coupon_type'] : 0;
	        $appliedTo = [];
            if ($type == 1) { // Applied to all
                $appliedTo = $coupon['applied_to']['applied_to_all'];
            } elseif ($type == 2) { // Applied to types
                $appliedTo = $coupon['applied_to']['applied_to_types'];
            } elseif ($type == 3) { // Applied to products
                $appliedTo = $coupon['applied_to']['applied_to_products'];
            }
            if (count($appliedTo)) {
                foreach ($appliedTo as $product) {
                    if ($product['order_product_type'] == $itemType && $product['order_product_id'] == $item['id']) {
                        $productDiscount += $product['discount'];
                    }
                }
            }

        }

        return $productDiscount;
    }

    /**
     * @param $cart
     * @param $item
     * @param $taxPercentage
     *
     * @return float|int
     */
    public function addTaxesDevices($cart, $item, $taxPercentage)
    {
        $itemTax = [];
        if ($item && $item['taxable']) {
            $amount = $cart['plan'] != null ? $item['amount_w_plan'] : $item['amount'];

            if ($this->couponAmount ) {
                $discounted = $this->getCouponPrice($this->couponAmount, $item, 2);
                $amount = $discounted > 0 && $amount > $discounted ? $amount - $discounted : 0;
            }
            $itemTax[] = $taxPercentage * $amount;
        }
        return !empty($itemTax) ? array_sum($itemTax) : 0;
    }

    /**
     * @param $cart
     * @param $item
     * @param $taxPercentage
     *
     * @return float|int
     */
    public function addTaxesSims($cart, $item, $taxPercentage)
    {
        $itemTax = [];
        if ($item && $item['taxable']) {
            $amount = $cart['plan'] != null ? $item['amount_w_plan'] : $item['amount_alone'];
            if ($this->couponAmount) {
                $discounted = $this->getCouponPrice($this->couponAmount, $item, 3);
                $amount = $discounted > 0 && $amount > $discounted ? $amount - $discounted : 0;
            }
            $itemTax[] = $taxPercentage * $amount;
        }
        return !empty($itemTax) ? array_sum($itemTax) : 0;
    }

    /**
     * @param $cart
     * @param $taxPercentage
     *
     * @return float|int
     */
    public function addTaxesToAddons($cart, $taxPercentage)
    {
        $addonTax = [];
        if ($cart['addons'] != null) {
            foreach ($cart['addons'] as $addon) {
                if ($addon['taxable'] == 1) {
                    $amount = $addon['prorated_amt'] != null ? $addon['prorated_amt'] : $addon['amount_recurring'];
                    if ($this->couponAmount) {
                        $discounted = $this->getCouponPrice($this->couponAmount, $addon, 4);
                        $amount = $discounted > 0 && $amount > $discounted ? $amount - $discounted : 0;
                    }
                    $addonTax[] = $taxPercentage * $amount;
                }
            }
        }
        return !empty($addonTax) ? array_sum($addonTax) : 0;
    }

    /**
     * It returns the array of Plan-prices from an array
     *
     * @param array $type
     * @return  array
     */
    protected function getOriginalPlanPrice()
    {
        if ($this->cartItems != null) {
            if (count($this->cartItems['order_groups'])) {
                foreach ($this->cartItems['order_groups'] as $cart) {
                    $this->prices[] = ($cart['plan'] != null) ? $cart['plan']['amount_recurring'] : [];
                }
            }
        }
        return true;
    }

    /**
     * It returns the array of Addon-prices from an array
     *
     * @param array $type
     * @return  array
     */
    protected function getOriginalAddonPrice()
    {
        if ($this->cartItems != null) {
            if (count($this->cartItems['order_groups'])) {
                foreach ($this->cartItems['order_groups'] as $cart) {
                    if ($cart['addons'] != null) {
                        foreach ($cart['addons'] as $addon) {
                            if ($addon['subscription_addon_id'] != null) {
                                $this->prices[] = [];
                            } else {
                                $this->prices[] = $addon['amount_recurring'];
                            }
                        }
                    }
                }
            }
        }
        return true;
    }

    /**
     * @param $coupon
     *
     * @return array
     */
    public function checkEligibleProducts($coupon)
    {
        $planRestriction = [];
        $isPercentage    = $coupon->fixed_or_perc == self::FIXED_PERC_TYPES['percentage'] ? true : false;
        if ($coupon->multiline_restrict_plans && $coupon->multilinePlanTypes->count()) {
            foreach ($coupon->multilinePlanTypes as $type) {
                if ($type->plan_type == self::PLAN_TYPE['Voice']) {
                    $planRestriction[] = 'Voice';
                } elseif ($type->plan_type == self::PLAN_TYPE['Data']) {
                    $planRestriction[] = 'Data';
                }
            }
        }

        $planRestriction = count($planRestriction) ? implode(', ',$planRestriction) : false;
        if ($coupon->class == Coupon::CLASSES['APPLIES_TO_ALL']) {
            $amount = $isPercentage == false ? '$'.$coupon->amount : $coupon->amount.'%';
            return [
                'details'            => implode('<br>', [
                    $amount. ' off on all products',
                    $planRestriction ? 'Plan restriction :'.$planRestriction : null
                ])
            ];
        } elseif ($coupon->class == Coupon::CLASSES['APPLIES_TO_SPECIFIC_TYPES']) {
            $couponTypes = $coupon->couponProductTypes;
            foreach ($couponTypes as $type) {
                $amount = $type->amount;
                $type_amount =  $isPercentage == false ? '$'.$amount : $amount.'%';
                $plans[]   = $type->type == self::SPECIFIC_TYPES['PLAN']   ? $type_amount.' off on plans'   : '';
                $devices[] = $type->type == self::SPECIFIC_TYPES['DEVICE'] ? $type_amount.' off on devices' : '';
                $sims[]    = $type->type == self::SPECIFIC_TYPES['SIM']    ? $type_amount.' off on sims'    : '';
                $addons[]  = $type->type == self::SPECIFIC_TYPES['ADDON']  ? $type_amount.' off on addons'  : '';
            }
            return [
                'details' => implode('<br>', array_filter(
                    [
                        implode('', $plans),
                        implode('', $devices),
                        implode('', $sims),
                        implode('', $addons),
                        $planRestriction ? 'Plan restriction :'.$planRestriction : null,
                        $coupon->num_cycles ? 'Cycles: Coupon applies for '. $coupon->num_cycles. ' billing cycles' : 'Cycles: Infinite coupon'
                    ],
                    'strlen'))
            ];
        } elseif ($coupon->class == Coupon::CLASSES['APPLIES_TO_SPECIFIC_PRODUCT']) {
            $couponProducts = $coupon->couponProducts;
            $plans   = [];
            $devices = [];
            $sims   = [];
            $addons = [];
            foreach ($couponProducts as $product) {
                $amount = $product->amount;
                $amount =  $isPercentage == false ? '$'.$amount : $amount.'%';
                $plans[]   = $product->product_type == self::SPECIFIC_TYPES['PLAN']   ? $amount.' off on plan '. $product->plan->name   : '';
                $devices[] = $product->product_type == self::SPECIFIC_TYPES['DEVICE'] ? $amount. ' off on device '. $product->device->name : '';
                $sims[]    = $product->product_type == self::SPECIFIC_TYPES['SIM']    ? $amount. ' off on sim '. $product->sim->name    : '';
                $addons[]  = $product->product_type == self::SPECIFIC_TYPES['ADDON']  ? $amount. ' off on addon '. $product->addon->name  : '';
            }

            return [
                'details' => implode('<p></p><br>', array_filter(
                    [
                        implode(', ', array_filter($plans, 'strlen')),
                        implode(', ', array_filter($devices, 'strlen')),
                        implode(', ', array_filter($sims, 'strlen')),
                        implode(', ', array_filter($addons, 'strlen')),
                        $planRestriction ? 'Plan restriction :'.$planRestriction : null,
                        $coupon->num_cycles ? 'Cycles: Coupon applies for '. $coupon->num_cycles. ' billing cycles' : 'Cycles: Infinite coupon'
                    ],
                    'strlen'))
            ];
        }
    }


    /**
     * @param $coupon
     * @param $order
     * @param $tax
     *
     * @return array
     */
    protected function appliedToAll($coupon, $order, $tax)
    {
        $isPercentage       = $coupon->fixed_or_perc == self::FIXED_PERC_TYPES['percentage'];
        $multilineRestrict  = $coupon->multiline_restrict_plans ? $coupon->multilinePlanTypes->pluck('plan_type')->toArray() : null;
        $countItems         = 0;
        $totalDiscount      = 0;
        $orderGroups        = $order->allOrderGroup;
        $orderCouponProduct = [];

        foreach ($orderGroups as $og) {
            // plan charges
            if ($og->plan_id) {
                $planData = $this->couponForPlans($og, $isPercentage, $coupon, $tax);
                if ($planData['discount'] == 0 || !$planData['discount']) continue;
                $totalDiscount += $planData['discount'];
                $orderCouponProduct[] = $planData['products'];
                $planData['discount'] ? $countItems++ : null;
            }
            // device charges
            if ($og->device_id) {
                $deviceData = $this->couponForDevice($og, $isPercentage, $coupon, $tax);
                if (!$deviceData['discount'] || $deviceData['discount'] == 0) continue;
                $totalDiscount += $deviceData['discount'];
                $orderCouponProduct[] = $deviceData['products'];
                $deviceData['discount'] ? $countItems++ : null;
            }
            // sim charges
            if ($og->sim_id) {
                $simData = $this->couponForSims($og, $isPercentage, $coupon, $tax);
                if ($simData['discount'] == 0 || !$simData['discount']) continue;
                $totalDiscount += $simData['discount'];
                $orderCouponProduct[] = $simData['products'];
                $simData['discount'] ? $countItems++ : null;
            }
            // addon charges
            if ($og->plan_id) {
                foreach ($og->addons as $addon) {
                    $addonData = $this->couponForAddons($order, $addon, $isPercentage, $coupon, $og, $tax);
                    if ($addonData['discount'] == 0 || !$addonData['discount']) continue;
                    $totalDiscount += $addonData['discount'];
                    $orderCouponProduct[] = $addonData['products'];
                    $addonData['discount'] ? $countItems++ : null;
                }
            }
        }
        $orderCouponProduct ? $this->orderCoupon($orderCouponProduct, $order) : null;
        return ([
            'total'      => str_replace(',', '', number_format($totalDiscount, 2)),
            'applied_to' => isset($orderCouponProduct) ? $orderCouponProduct : [],
            'amount'     => $coupon->amount
        ]);
    }

    /**
     * @param $couponMain
     * @param $order
     * @param $tax
     *
     * @return array
     */
    protected function appliedToTypes($couponMain, $order, $tax)
    {
        $isPercentage       = $couponMain->fixed_or_perc == self::FIXED_PERC_TYPES['percentage'];
        $multilineRestrict  = $couponMain->multiline_restrict_plans ? $couponMain->multilinePlanTypes->pluck('plan_type')->toArray() : null;
        $totalDiscount      = 0;

        foreach ($couponMain->couponProductTypes as $coupon) {
            foreach ($order->allOrderGroup as $og) {
                // For Device types
                if ($coupon->type == self::SPECIFIC_TYPES['DEVICE'] && $og->device_id) {
                    $amountForDevices    = $coupon->amount;
                    $deviceData         = $this->couponForDevice($og, $isPercentage, $coupon, $tax);
                    if (!$deviceData['discount'] || $deviceData['discount'] == 0) continue;
                    $totalDiscount      += $deviceData['discount'];
                    $orderCouponProduct[] = $deviceData['products'];
                }
                // For Plan types
                if ($coupon->type == self::SPECIFIC_TYPES['PLAN'] && $og->plan_id) {
                    $amountForPlans     = $coupon->amount;
                    if ($coupon->sub_type && $og->plan->type != $coupon->sub_type) continue;
                    $planData           = $this->couponForPlans($og, $isPercentage, $coupon, $tax);
                    if ($planData['discount'] == 0 || !$planData['discount']) continue;
                    $totalDiscount += $planData['discount'];
                    $orderCouponProduct[] = $planData['products'];
                }
                // For Sim types
                if ($coupon['type'] == self::SPECIFIC_TYPES['SIM'] && $og->sim_id) {
                    $amountForSims      = $coupon->amount;
                    $simData            = $this->couponForSims($og, $isPercentage, $coupon, $tax);
                    if ($simData['discount'] == 0 || !$simData['discount']) continue;
                    $totalDiscount += $simData['discount'];
                    $orderCouponProduct[] = $simData['products'];
                }
                //For Addon types
                if ($coupon['type'] == self::SPECIFIC_TYPES['ADDON'] && $og->addons->count()) {
                    foreach ($og->addons as $addon) {
                        $amountForAddons   = $coupon->amount;
                        $addonData = $this->couponForAddons($order, $addon, $isPercentage, $coupon, $og, $tax);
                        if ($addonData['discount'] == 0 || !$addonData['discount']) continue;
                        $totalDiscount += $addonData['discount'];
                        $orderCouponProduct[] = $addonData['products'];
                    }
                }
            }
        }
        isset($orderCouponProduct) ? $this->orderCoupon($orderCouponProduct, $order) : null;
        return ([
            'total'         => str_replace(',', '', $totalDiscount),
            'applied_to'    => isset($orderCouponProduct) ? $orderCouponProduct : [],
            'amount'        => [
                'plan'  => isset($amountForPlans) ? $amountForPlans : 0,
                'device'=> isset($amountForDevices) ? $amountForDevices : 0,
                'sims'  => isset($amountForSims) ? $amountForSims : 0,
                'addons'=> isset($amountForAddons) ? $amountForAddons : 0,
            ]
        ]);
    }

    /**
     * @param $couponMain
     * @param $order
     * @param $tax
     *
     * @return array
     */
    protected function appliedToProducts($couponMain, $order, $tax)
    {
        $isPercentage       = $couponMain['fixed_or_perc'] == self::FIXED_PERC_TYPES['percentage'];
        $multilineRestrict  = $couponMain['multiline_restrict_plans'] == 1 ? $couponMain->multilinePlanTypes->pluck('plan_type')->toArray() : null;
        $totalDiscount      = 0;

        foreach ($couponMain->couponProducts as $coupon) {
            foreach ($order->allOrderGroup as $og) {
                // For plans
                if ($coupon->product_type == self::SPECIFIC_TYPES['PLAN'] && $coupon->product_id == $og->plan_id) {
                    $planData = $this->couponForPlans($og, $isPercentage, $coupon, $tax);
                    if ($planData['discount'] == 0 || !$planData['discount']) continue;
                    $totalDiscount += $planData['discount'];
                    $orderCouponProduct[] = $planData['products'];
                }
                // For devices
                if ($coupon->product_type == self::SPECIFIC_TYPES['DEVICE'] && $coupon->product_id == $og->device_id) {
                    $deviceData = $this->couponForDevice($og, $isPercentage, $coupon, $tax);
                    if (!$deviceData['discount'] || $deviceData['discount'] == 0) continue;
                    $totalDiscount += $deviceData['discount'];
                    $orderCouponProduct[] = $deviceData['products'];
                }
                // For Sims
                if ($coupon->product_type == self::SPECIFIC_TYPES['SIM'] && $coupon->product_id == $og->sim_id) {
                    $simData = $this->couponForSims($og, $isPercentage, $coupon, $tax);
                    if ($simData['discount'] == 0 || !$simData['discount']) continue;
                    $totalDiscount += $simData['discount'];
                    $orderCouponProduct[] = $simData['products'];
                }
                // For Addons
                if ($coupon->product_type == self::SPECIFIC_TYPES['ADDON'] && $og->addons->count()) {
                    foreach ($og->addons as $addon) {
                        if ($addon->id != $coupon->product_id) continue;
                        $addonData = $this->couponForAddons($order, $addon, $isPercentage, $coupon, $og, $tax);
                        if ($addonData['discount'] == 0 || !$addonData['discount']) continue;
                        $totalDiscount += $addonData['discount'];
                        $orderCouponProduct[] = $addonData['products'];
                    }
                }
            }
        }
        isset($orderCouponProduct) ? $this->orderCoupon($orderCouponProduct, $order) : null;
        return ([
            'total'         => str_replace(',', '', $totalDiscount),
            'applied_to'    => isset($orderCouponProduct) ? $orderCouponProduct : [],
        ]);
    }

    /**
     * @param $og
     * @param $isPercentage
     * @param $coupon
     * @param $tax
     *
     * @return array
     */
    protected function couponForPlans($og, $isPercentage, $coupon, $tax)
    {
        $planAmount = $og->plan_prorated_amt ?: $og->plan->amount_recurring;
        $planAmount += $og->plan->amount_onetime ?: 0;
        $planDiscount = ($isPercentage ? $coupon->amount * $planAmount / 100 : $coupon->amount);
        /**
         * @internal Rule that coupon can never exceed the original cost
         */
        $discountAmount = $planDiscount > $planAmount ? $planAmount : $planDiscount;
        $orderCouponProduct = $this->orderCouponProducts(self::SPECIFIC_TYPES['PLAN'], $og->plan_id, $coupon->amount, $discountAmount, $og->id);
        $this->totalTaxableAmount[] = $og->plan->taxable && $planAmount > $discountAmount ? $planAmount - $discountAmount : 0;
        return ['discount' => $discountAmount, 'products' => $orderCouponProduct];
    }

    /**
     * @param $productType
     * @param $productId
     * @param $couponAmount
     * @param $discount
     * @param $orderGroupId
     *
     * @return array
     */
    protected function orderCouponProducts($productType, $productId, $couponAmount, $discount, $orderGroupId)
    {
        return [
            'order_product_type'    => $productType,
            'order_product_id'      => $productId,
            'amount'                => $couponAmount,
            'discount'              => $discount,
            'order_group_id'        => $orderGroupId,
        ];
    }

    /**
     * @param $order
     * @param $addon
     * @param $isPercentage
     * @param $coupon
     * @param $og
     * @param $tax
     *
     * @return array
     */
    protected function couponForAddons($order, $addon, $isPercentage, $coupon, $og, $tax)
    {
        $addonAmount = $order->addonProRate($addon->id) ?: $addon->amount_recurring;
        $addonDiscount  = ($isPercentage ? $coupon->amount * $addonAmount / 100 : $coupon->amount);
        /**
         * @internal Rule that coupon can never exceed the original cost
         */
        $discountAmount = $addonDiscount > $addonAmount ? $addonAmount : $addonDiscount;
        $orderCouponProduct = $this->orderCouponProducts(self::SPECIFIC_TYPES['ADDON'], $addon->id, $coupon->amount, $discountAmount, $og->id);

        $this->totalTaxableAmount[] = $addon->taxable && $addonAmount > $discountAmount ? $addonAmount - $discountAmount : 0;
        return ['discount' => $discountAmount, 'products' => $orderCouponProduct];
    }

    /**
     * @param $data
     * @param $order
     */
    protected function orderCoupon($data, $order)
    {
        $orderCoupons = $order->orderCoupon;
        foreach($orderCoupons as $orderCoupon) {
            $orderCoupon->orderCouponProduct()->delete();
            if (count($data)) {
                foreach ($data as $product) {
                    $orderCoupon->orderCouponProduct()->create([
                        'order_product_type'    => $product['order_product_type'],
                        'order_product_id'      => $product['order_product_id'],
                        'amount'                => $product['amount']
                    ]);
                }
            }
        }
    }

    /**
     * @param $og
     * @param $isPercentage
     * @param $coupon
     * @param $tax
     *
     * @return array
     */
    protected function couponForSims($og, $isPercentage, $coupon, $tax)
    {
        $simAmount = $og->plan_id ? $og->sim->amount_w_plan : $og->sim->amount_alone;
        $simDiscount =  ($isPercentage ? $coupon->amount * $simAmount / 100 : $coupon->amount);
        /**
         * @internal Rule that coupon can never exceed the original cost
         */
        $discountAmount = $simDiscount > $simAmount ? $simAmount : $simDiscount;
        $orderCouponProduct = $this->orderCouponProducts(self::SPECIFIC_TYPES['SIM'], $og->sim_id, $coupon->amount, $discountAmount, $og->id);
        $this->totalTaxableAmount[] = $og->sim->taxable && $simAmount > $discountAmount ? $simAmount - $discountAmount : 0;
        return ['discount' => $discountAmount, 'products' => $orderCouponProduct];
    }

    /**
     * @param $og
     * @param $isPercentage
     * @param $coupon
     * @param $tax
     *
     * @return array
     */
    protected function couponForDevice($og, $isPercentage, $coupon, $tax)
    {
        $deviceAmount = $og->plan_id ? $og->device->amount_w_plan : $og->device->amount;
        $deviceDiscount = ($isPercentage ? $coupon->amount * $deviceAmount / 100 : $coupon->amount);

        /**
         * @internal Rule that coupon can never exceed the original cost
         */
        $discountAmount = $deviceDiscount > $deviceAmount ? $deviceAmount : $deviceDiscount;
        $orderCouponProduct = $this->orderCouponProducts(self::SPECIFIC_TYPES['DEVICE'], $og->device_id, $coupon->amount, $discountAmount, $og->id);
        $this->totalTaxableAmount[] = $og->device->taxable && $deviceAmount > $discountAmount ? $deviceAmount - $discountAmount : 0;
        return ['discount' => $discountAmount, 'products' => $orderCouponProduct];
    }

    /**
     * @param       $order
     * @param       $customer
     * @param       $coupon
     * @param false $admin
     *
     * @return bool
     */
    protected function isApplicable($order, $customer, $coupon, $admin = false)
    {
        if ($admin) {
            $totalSubscriptions = $customer->billableSubscriptionsForCoupons->count();
        } else {
            $accountSubscriptions   = $customer->billableSubscriptionsForCoupons;
            $cartSubscriptions      = $order->allOrderGroup->where('plan_id', '!=', null);
            $totalSubscriptions     = $accountSubscriptions->count() + $cartSubscriptions->count();
            $multilineRestrict  = $coupon->multiline_restrict_plans ? $coupon->multilinePlanTypes->pluck('plan_type')->toArray() : null;
            if ($multilineRestrict) {
                $totalIds = array_merge($accountSubscriptions->pluck('plan_id')->toArray(), $cartSubscriptions->pluck('plan_id')->toArray());
                $eligibleSubs = array_filter($cartSubscriptions->pluck('plan_id')->toArray(), function ($id) use ($multilineRestrict) {
                    $plan = Plan::find($id);
                    if (in_array($plan->type, $multilineRestrict)) {
                        return $plan->id;
                    }
                });
                if (!count($eligibleSubs)) {
                    $this->failedResponse = 'Requirements not met';
                    return false;
                }
            }
        }
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

    /**
     * @param $coupon
     *
     * @return bool
     */
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
}