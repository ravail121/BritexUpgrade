<?php

namespace Tests\Feature\Invoice;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Model\Device;
use App\Model\Customer;
use App\Model\Sim;
use App\Model\Plan;
use App\Model\Addon;
use App\Model\Order;
use App\Model\Coupon;
use App\Model\CouponProduct;
use App\Model\Credit;
use App\Model\CouponProductType;
use App\Model\Tax;

class InvoiceTests extends TestCase
{
    use WithFaker;
    use DatabaseTransactions;

	const HEADER_DATA = ['Authorization' => 'alar324r23423'];

    public function test_device_only()
    {
        $randomDevice   = Device::inRandomOrder()->limit(1)->first();
        $customers      = Customer::inRandomOrder()
                            ->whereNotNull('billing_fname')
                            ->whereNotNull('shipping_fname')->get();
        $insertOrder          = $this->withHeaders(self::HEADER_DATA)->post('api/order');
        $order = Order::find($insertOrder->json()['id']);
     
        foreach ($customers as $customer) {
            
            if (count($customer->customerCreditCards)) {

                $tax            = isset($customer->stateTax) && $randomDevice['taxable'] ? ($randomDevice['amount'] * $customer->stateTax->rate) / 100 : 0;
                $shipping       = $randomDevice['shipping_fee'];
                $totalInvoice   = $randomDevice['amount'] + $shipping + $tax;
                
                $customerStandaloneDevice = $this->withHeaders(self::HEADER_DATA)->post('api/create-device-record?'.http_build_query([
                    'api_key'       => self::HEADER_DATA['Authorization'],
                    'order_id'      => $order['id'],
                    'device_id'     => $randomDevice['id'],
                    'customer_id'   => $customer['id']
                ]));

                $updateOrder  = $this->withHeaders(self::HEADER_DATA)->post('api/create-customer?'.http_build_query(
                    [
                        'customer_id'   => $customer['id'],
                        'order_hash'    => $order['hash']
                    ]));
                    
                $saveInvoice  = $this->withHeaders(self::HEADER_DATA)->post('api/charge-new-card?'.http_build_query([
                    'billing_fname'     => $customer['billing_fname'],
                    'billing_lname'     => $customer['billing_lname'],
                    'billing_address1'  => $customer['billing_address1'],
                    'billing_address2'  => $customer['billing_address1'],
                    'billing_city'      => $customer['city'],
                    'billing_state_id'  => $customer['billing_state_id'],
                    'billing_zip'       => $customer['zip'],
                    'coupon'            => NULL,
                    'customer_card'     => $customer->customerCreditCards->first()['id'],
                    'auto_pay'          => '0',
                    'payment_card_no'   => $customer->customerCreditCards->first()['last4'],
                    'payment_card_holder' => $customer->customerCreditCards->first()['cardholder'],
                    'expires_mmyy'      => $customer->customerCreditCards->first()['expiration'],
                    'payment_cvc'       => $customer->customerCreditCards->first()['cvc'],
                    'amount'            => $totalInvoice,
                    'customer_id'       => $order['customer_id'],
                    'order_hash'        => $order['hash'],
                ]));
            
                $invoiceItems = $this->withHeaders(self::HEADER_DATA)->post('api/generate-one-time-invoice?'.http_build_query(
                [
                    'data_to_invoice'   => ['customer_standalone_device_id' => 
                    [
                        $customerStandaloneDevice->json()['device_id']]
                    ],
                    'customer_id'       => $order['customer_id'],
                    'hash'              => $order['hash'],
                    'order_id'          => $order['id']
                ]));

                return  $customerStandaloneDevice->assertJson(['device_id' => $order->standAloneDevices->first()['id']]) &&
                        $saveInvoice->assertJson(['success' => true]) &&
                        $invoiceItems->assertSeeText('Invoice item generated successfully')->assertJson(
                            [
                                'invoice_items_total' => number_format($totalInvoice, 2)
                            ]
                        );

            }
        }

    }

    public function test_sim_only()
    {
        $randomsim      = Sim::inRandomOrder()->limit(1)->first();
        $customers      = Customer::inRandomOrder()
                            ->whereNotNull('billing_fname')
                            ->whereNotNull('shipping_fname')->get();
        $insertOrder    = $this->withHeaders(self::HEADER_DATA)->post('api/order');
        $order = Order::find($insertOrder->json()['id']);

        foreach ($customers as $customer) {
            
            if (count($customer->customerCreditCards)) {

                $tax            = isset($customer->stateTax) && $randomsim['taxable'] ? ($randomsim['amount_alone'] * $customer->stateTax->rate) / 100 : 0;
                $shipping       = $randomsim['shipping_fee'] ?: 0;
                $totalInvoice   = $randomsim['amount_alone'] + $shipping + $tax;
                
                $customerStandalonesim = $this->withHeaders(self::HEADER_DATA)->post('api/create-sim-record?'.http_build_query([
                    'api_key'       => self::HEADER_DATA['Authorization'],
                    'order_id'      => $order['id'],
                    'sim_id'        => $randomsim['id'],
                    'customer_id'   => $customer['id']
                ]));
                
                $updateOrder  = $this->withHeaders(self::HEADER_DATA)->post('api/create-customer?'.http_build_query(
                    [
                        'customer_id'   => $customer['id'],
                        'order_hash'    => $order['hash']
                    ]));

                $saveInvoice  = $this->withHeaders(self::HEADER_DATA)->post('api/charge-new-card?'.http_build_query([
                    'billing_fname'     => $customer['billing_fname'],
                    'billing_lname'     => $customer['billing_lname'],
                    'billing_address1'  => $customer['billing_address1'],
                    'billing_address2'  => $customer['billing_address1'],
                    'billing_city'      => $customer['city'],
                    'billing_state_id'  => $customer['billing_state_id'],
                    'billing_zip'       => $customer['zip'],
                    'coupon'            => NULL,
                    'customer_card'     => $customer->customerCreditCards->first()['id'],
                    'auto_pay'          => '0',
                    'payment_card_no'   => $customer->customerCreditCards->first()['last4'],
                    'payment_card_holder' => $customer->customerCreditCards->first()['cardholder'],
                    'expires_mmyy'      => $customer->customerCreditCards->first()['expiration'],
                    'payment_cvc'       => $customer->customerCreditCards->first()['cvc'],
                    'amount'            => $totalInvoice,
                    'customer_id'       => $order['customer_id'],
                    'order_hash'        => $order['hash'],
                ]));
            
                $invoiceItems = $this->withHeaders(self::HEADER_DATA)->post('api/generate-one-time-invoice', [
                    'data_to_invoice'   => ['customer_standalone_sim_id' => [$customerStandalonesim->json()['sim_id']]],
                    'customer_id'       => $order['customer_id'],
                    'hash'              => $order['hash'],
                    'order_id'          => $order['id']
                ]);
                
                return  $customerStandalonesim->assertJson(['sim_id' => $order->standAloneSims->first()['id']]) &&
                        $saveInvoice->assertJson(['success' => true]) &&
                        $invoiceItems->assertSeeText('Invoice item generated successfully')->assertJson(
                            [
                                'invoice_items_total' => number_format($totalInvoice, 2)
                            ]
                        );

            }

        }

    }

    public function test_plan_only()
    {
        $customers      = Customer::inRandomOrder()
                            ->whereNotNull('billing_fname')
                            ->whereNotNull('shipping_fname')->get();
        $randomPlan     = Plan::inRandomOrder()->limit(1)->first();
        $hash           = sha1(time());
        foreach ($customers as $customer) {
            
            if (count($customer->customerCreditCards)) {

                $storeOrder = $this->withHeaders(self::HEADER_DATA)->post('api/order');
                
                $order = Order::find($storeOrder->json()['id']);

                $updateOrder  = $this->withHeaders(self::HEADER_DATA)->post('api/create-customer?'.http_build_query(
                    [
                        'customer_id'   => $customer['id'],
                        'order_hash'    => $order['hash']
                    ]));
                $order = Order::where('hash', $order['hash'])->first();
                $planAmount         = number_format($order->planProRate($randomPlan['id']), 2);
                $tax                = number_format(isset($customer->stateTax) && $randomPlan['taxable'] ? ($planAmount * $customer->stateTax->rate) / 100 : 0, 2);
                $regulatory         = number_format($randomPlan['regulatory_fee_type'] == 1 ? $randomPlan['regulatory_fee_amount'] : $planAmount * $randomPlan['regulatory_fee_amount'] / 100, 2);
                $totalInvoice       = $planAmount + $tax + $regulatory + $randomPlan['amount_onetime'];

                $saveInvoice  = $this->withHeaders(self::HEADER_DATA)->post('api/charge-new-card?'.http_build_query([
                    'billing_fname'     => $customer['billing_fname'],
                    'billing_lname'     => $customer['billing_lname'],
                    'billing_address1'  => $customer['billing_address1'],
                    'billing_address2'  => $customer['billing_address1'],
                    'billing_city'      => $customer['city'],
                    'billing_state_id'  => $customer['billing_state_id'],
                    'billing_zip'       => $customer['zip'],
                    'coupon'            => NULL,
                    'customer_card'     => $customer->customerCreditCards->first()['id'],
                    'auto_pay'          => '0',
                    'payment_card_no'   => $customer->customerCreditCards->first()['last4'],
                    'payment_card_holder' => $customer->customerCreditCards->first()['cardholder'],
                    'expires_mmyy'      => $customer->customerCreditCards->first()['expiration'],
                    'payment_cvc'       => $customer->customerCreditCards->first()['cvc'],
                    'amount'            => number_format($totalInvoice, 2),
                    'customer_id'       => $order['customer_id'],
                    'order_hash'        => $order['hash'],
                ]));

                $subscription = $this->withHeaders(self::HEADER_DATA)->post('api/create-subscription?'.http_build_query([
                    'api_key'          => self::HEADER_DATA['Authorization'],
                    'order_id'         => $order['id'],
                    'plan_id'          => $randomPlan->id,
                    'sim_num'          => '2314567891234561234',
                    'sim_type'         => 'Sample Name',
                ]));

                $invoiceItems = $this->withHeaders(self::HEADER_DATA)->post('api/generate-one-time-invoice', 
                    [
                        'data_to_invoice'   => ['subscription_id' => [$order->subscriptions->first()['id']]],
                        'customer_id'       => $saveInvoice->json()['card']['card']['customer_id'],
                        'hash'              => $order['hash'],
                        'couponAmount'      => 0,
                        'couponCode'        => NULL,
                        'order_id'          => $order['id']
                    ]);
                
                return  $storeOrder->assertJson(['order_hash' => $order->hash]) &&
                        $subscription->assertJson([
                            'success' => true,
                            'subscription_id' => $order->subscriptions->first()['id']]) && 
                        $invoiceItems->assertSeeText('Invoice item generated successfully')->assertJson(
                                            [
                                                'invoice_items_total' => number_format($totalInvoice, 2)
                                            ]
                                        );

            }
        }
    }

    public function test_complete_subscription()
    {
        $customers      = Customer::inRandomOrder()
                            ->whereNotNull('billing_fname')
                            ->whereNotNull('shipping_fname')->get();
        $randomPlan     = Plan::inRandomOrder()->limit(1)->first();
        $randomSim      = Sim::inRandomOrder()->limit(1)->first();
        $randomDevice   = Device::inRandomOrder()->limit(1)->first();
        $randomAddon    = Addon::inRandomOrder()->limit(1)->first();
        $hash           = sha1(time());
        
        foreach ($customers as $customer) {
            
            if (count($customer->customerCreditCards)) {

                $storeOrder         = $this->withHeaders(self::HEADER_DATA)->post('api/order?'.http_build_query([
                    'hash'          => $hash,
                    'company_id'    => $customer->company_id
                ]));
                $order = Order::find($storeOrder->json()['id']);
                $updateOrder  = $this->withHeaders(self::HEADER_DATA)->post('api/create-customer?'.http_build_query(
                    [
                        'customer_id'   => $customer['id'],
                        'order_hash'    => $order['hash']
                    ]));

                $order = Order::where('hash', $order['hash'])->first();
        
                $planAmount         = number_format($order->planProRate($randomPlan['id']), 2);
                $addonAmount        = number_format($order->addonProRate($randomAddon['id']), 2);
                $tax                = isset($customer->stateTax) ? ($customer->stateTax->rate) : 0;
                $taxableAmount      = [
                    $randomPlan['taxable'] ? $planAmount * $tax / 100 : 0,
                    $randomDevice['taxable'] ? $randomDevice['amount_w_plan'] * $tax / 100 : 0,
                    $randomSim['taxable'] ? $randomSim['amount_w_plan'] * $tax / 100 : 0,
                    $randomAddon['taxable'] ? $addonAmount * $tax / 100 : 0
                ];
        
                $totalShipping      = [
                    $randomDevice['shipping_fee'] ?: 0, $randomSim['shipping_fee'] ?: 0
                ];
                $regulatory         = $randomPlan['regulatory_fee_type'] == 1 ? $randomPlan['regulatory_fee_amount'] : number_format($planAmount * $randomPlan['regulatory_fee_amount'] / 100, 2);
                $device             = $randomDevice['amount_w_plan'];
                $sim                = $randomSim['amount_w_plan'];
                $total              = ($device + $sim + $planAmount + $regulatory + $addonAmount + array_sum($totalShipping) + $randomPlan['amount_onetime']) + number_format(array_sum($taxableAmount), 2);
                
                $subscription = $this->withHeaders(self::HEADER_DATA)->post('api/create-subscription?'.http_build_query([
                    'api_key'   => self::HEADER_DATA['Authorization'],
                    'order_id'  => $order['id'],
                    'device_id' => $randomDevice['id'],
                    'plan_id'   => $randomPlan['id'],
                    'sim_id'    => $randomSim['id'],
                ]));
        
                $subscriptionAddon = $this->withHeaders(self::HEADER_DATA)->post('api/create-subscription-addon?'.http_build_query([
                    'api_key' => self::HEADER_DATA['Authorization'],
                    'order_id' => $order['id'],
                    'subscription_id' => $subscription->json()['subscription_id'],
                    'addon_id' => $randomAddon['id'],
                    'plan_id' => $randomPlan['id'],
                ]));
        
                $saveInvoice  = $this->withHeaders(self::HEADER_DATA)->post('api/charge-new-card?'.http_build_query([
                    'billing_fname'     => $customer['billing_fname'],
                    'billing_lname'     => $customer['billing_lname'],
                    'billing_address1'  => $customer['billing_address1'],
                    'billing_address2'  => $customer['billing_address1'],
                    'billing_city'      => $customer['city'],
                    'billing_state_id'  => $customer['billing_state_id'],
                    'billing_zip'       => $customer['zip'],
                    'coupon'            => NULL,
                    'customer_card'     => $customer->customerCreditCards->first()['id'],
                    'auto_pay'          => '0',
                    'payment_card_no'   => $customer->customerCreditCards->first()['last4'],
                    'payment_card_holder' => $customer->customerCreditCards->first()['cardholder'],
                    'expires_mmyy'      => $customer->customerCreditCards->first()['expiration'],
                    'payment_cvc'       => $customer->customerCreditCards->first()['cvc'],
                    'amount'            => number_format($total, 2),
                    'customer_id'       => $order['customer_id'],
                    'order_hash'        => $order['hash'],
                ]));
        
                $invoiceItems = $this->withHeaders(self::HEADER_DATA)->post('api/generate-one-time-invoice', 
                [
                    'data_to_invoice'   => [
                        'subscription_id' => [$order->subscriptions->first()['id']],
                        'subscription_addon_id' => [$order->subscriptions->first()->subscriptionAddon->first()['id']]
                    ],
                    'customer_id'       => $saveInvoice->json()['card']['card']['customer_id'],
                    'hash'              => $order['hash'],
                    'couponAmount'      => 0,
                    'couponCode'        => NULL,
                    'order_id'          => $order['id']
                ]);
        
                return  $storeOrder->assertJson(['order_hash' => $order->hash]) &&
                        $subscription->assertJson([
                            'success' => true,
                            'subscription_id' => $order->subscriptions->first()['id']]) && 
                        $subscriptionAddon->assertJson(['subscription_addon_id' => $order->subscriptions->first()->subscriptionAddon->first()['id']]) &&
                        $saveInvoice->assertJson(['success' => true]) &&
                        $invoiceItems->assertSeeText('Invoice item generated successfully')->assertJson(['invoice_items_total' => number_format($total, 2)]);

            }
        }

    }

    public function test_subscrption_standalone_together()
    {
        $customers          = Customer::inRandomOrder()
                                ->whereNotNull('billing_fname')
                                ->whereNotNull('shipping_fname')->get();
        $randomPlan         = Plan::inRandomOrder()->limit(1)->first();
        $randomSim          = Sim::inRandomOrder()->limit(1)->first();
        $randomDevice       = Device::inRandomOrder()->limit(2)->get();
        $randomAddon        = Addon::inRandomOrder()->limit(1)->first();
        $hash               = sha1(time());

        foreach ($customers as $customer) {
            
            if (count($customer->customerCreditCards)) {

                $storeOrder         = $this->withHeaders(self::HEADER_DATA)->post('api/order?'.http_build_query([
                    'hash'          => $hash,
                    'company_id'    => $customer->company_id
                ]));
        
                $order = Order::find($storeOrder->json()['id']);
                $order->update(['customer_id' => $customer['id']]);
        
                $planAmount         = number_format($order->planProRate($randomPlan['id']), 2);
                $addonAmount        = number_format($order->addonProRate($randomAddon['id']), 2);
                $tax                = isset($customer->stateTax) ? ($customer->stateTax->rate) : 0;
                $taxableAmount      = [
                    $randomPlan['taxable'] ? $planAmount * $tax / 100 : 0,
                    $randomDevice->first()['taxable'] ? $randomDevice->first()['amount_w_plan'] * $tax / 100 : 0,
                    $randomSim['taxable'] ? $randomSim['amount_w_plan'] * $tax / 100 : 0,
                    $randomAddon['taxable'] ? $addonAmount * $tax / 100 : 0,
                    $randomDevice->last()['taxable'] ? $randomDevice->last()['amount'] * $tax / 100 : 0
                ];

                $totalShipping      = [
                    $randomDevice->first()['shipping_fee'] ?: 0,  $randomDevice->last()['shipping_fee'] ?: 0, $randomSim['shipping_fee'] ?: 0
                ];
                $regulatory         = $randomPlan['regulatory_fee_type'] == 1 ? $randomPlan['regulatory_fee_amount'] : number_format($planAmount * $randomPlan['regulatory_fee_amount'] / 100, 2);
                $device             = $randomDevice->first()['amount_w_plan'] + $randomDevice->last()['amount'];
                $sim                = $randomSim['amount_w_plan'];
                $total              = ($device + $sim + $planAmount + $regulatory + $addonAmount + array_sum($totalShipping) + $randomPlan['amount_onetime']) + number_format(array_sum($taxableAmount), 2);

                $customerStandaloneDevice = $this->withHeaders(self::HEADER_DATA)->post('api/create-device-record?'.http_build_query([
                    'api_key'       => self::HEADER_DATA['Authorization'],
                    'order_id'      => $order['id'],
                    'device_id'     => $randomDevice->last()['id'],
                    'customer_id'   => $customer['id']
                ]));
                
                $subscription = $this->withHeaders(self::HEADER_DATA)->post('api/create-subscription?'.http_build_query([
                    'api_key'   => self::HEADER_DATA['Authorization'],
                    'order_id'  => $order['id'],
                    'device_id' => $randomDevice->first()['id'],
                    'plan_id'   => $randomPlan['id'],
                    'sim_id'    => $randomSim['id'],
                ]));
        
                $subscriptionAddon = $this->withHeaders(self::HEADER_DATA)->post('api/create-subscription-addon?'.http_build_query([
                    'api_key' => self::HEADER_DATA['Authorization'],
                    'order_id' => $order['id'],
                    'subscription_id' => $subscription->json()['subscription_id'],
                    'addon_id' => $randomAddon['id'],
                    'plan_id' => $randomPlan['id'],
                ]));
        
                $saveInvoice  = $this->withHeaders(self::HEADER_DATA)->post('api/charge-new-card?'.http_build_query([
                    'billing_fname'     => $customer['billing_fname'],
                    'billing_lname'     => $customer['billing_lname'],
                    'billing_address1'  => $customer['billing_address1'],
                    'billing_address2'  => $customer['billing_address1'],
                    'billing_city'      => $customer['city'],
                    'billing_state_id'  => $customer['billing_state_id'],
                    'billing_zip'       => $customer['zip'],
                    'coupon'            => NULL,
                    'customer_card'     => $customer->customerCreditCards->first()['id'],
                    'auto_pay'          => '0',
                    'payment_card_no'   => $customer->customerCreditCards->first()['last4'],
                    'payment_card_holder' => $customer->customerCreditCards->first()['cardholder'],
                    'expires_mmyy'      => $customer->customerCreditCards->first()['expiration'],
                    'payment_cvc'       => $customer->customerCreditCards->first()['cvc'],
                    'amount'            => $total,
                    'customer_id'       => $order['customer_id'],
                    'order_hash'        => $order['hash'],
                ]));
        
                $invoiceItems = $this->withHeaders(self::HEADER_DATA)->post('api/generate-one-time-invoice', 
                [
                    'data_to_invoice'   => [
                        'subscription_id' => [$order->subscriptions->first()['id']],
                        'subscription_addon_id' => [$order->subscriptions->first()->subscriptionAddon->first()['id']],
                        'customer_standalone_device_id' => [$customerStandaloneDevice->json()['device_id']]
                    ],
                    'customer_id'       => $saveInvoice->json()['card']['card']['customer_id'],
                    'hash'              => $order['hash'],
                    'couponAmount'      => 0,
                    'couponCode'        => NULL,
                    'order_id'          => $order['id']
                ]);
        
                return  $storeOrder->assertJson(['order_hash' => $order->hash]) &&
                        $subscription->assertJson([
                            'success' => true,
                            'subscription_id' => $order->subscriptions->first()['id']]) && 
                        $subscriptionAddon->assertJson(['subscription_addon_id' => $order->subscriptions->first()->subscriptionAddon->first()['id']]) &&
                        $saveInvoice->assertJson(['success' => true]) &&
                        $invoiceItems->assertSeeText('Invoice item generated successfully')->assertJson(['invoice_items_total' => $total]);

            }
        }
    }

    public function test_standalone_device_with_coupon()
    {
        $randomDevice   = Device::inRandomOrder()->limit(1)->first();
        $customers      = Customer::inRandomOrder()
                            ->whereNotNull('billing_fname')
                            ->whereNotNull('shipping_fname')->get();
        $insertOrder          = $this->withHeaders(self::HEADER_DATA)->post('api/order');
        
        foreach ($customers as $customer) {
            
            if (count($customer->customerCreditCards)) {

                $randomCode     = str_random(10);
                Coupon::create([
                            'company_id'    => $customer['company_id'],
                            'active'        => 1,
                            'class'         => 3,
                            'fixed_or_perc' => 2,
                            'amount'        => 40,
                            'code'          => $randomCode,
                            'num_cycles'    => 10,
                            'max_uses'      => 100,
                            'num_uses'      => 1,
                            'stackable'     => 1,
                            'start_date'    => '2019-07-10 18:09:57	',
                            'end_date'      => '2021-07-10 18:09:57	',
                            'multiline_min' => 1,
                            'multiline_max' => 2,
                            'multiline_restrict_plans' => 0
                        ]);
                $coupon = Coupon::where('code', $randomCode)->first();
                CouponProduct::create([
                    'coupon_id' => $coupon->id,
                    'product_type' => 2,
                    'product_id' => $randomDevice->id,
                    'amount' => 40
                ]);

                $tax            = number_format(isset($customer->stateTax) && $randomDevice['taxable'] ? ($randomDevice['amount'] * $customer->stateTax->rate) / 100 : 0, 2);
                $shipping       = number_format($randomDevice['shipping_fee'], 2);
                $discount       = $randomDevice->amount * 40 / 100;
                $totalInvoice   = number_format($randomDevice['amount'] + $shipping + $tax - $discount, 2);
                
                $order = Order::find($insertOrder->json()['id']);
                $updateOrder  = $this->withHeaders(self::HEADER_DATA)->post('api/create-customer?'.http_build_query(
                    [
                        'customer_id'   => $customer['id'],
                        'order_hash'    => $order['hash']
                    ]));

                $order = Order::where('hash', $order['hash'])->first();
                $saveInvoice  = $this->withHeaders(self::HEADER_DATA)->post('api/charge-new-card?'.http_build_query([
                    'billing_fname'     => $customer['billing_fname'],
                    'billing_lname'     => $customer['billing_lname'],
                    'billing_address1'  => $customer['billing_address1'],
                    'billing_address2'  => $customer['billing_address1'],
                    'billing_city'      => $customer['city'],
                    'billing_state_id'  => $customer['billing_state_id'],
                    'billing_zip'       => $customer['zip'],
                    'coupon'            => NULL,
                    'customer_card'     => $customer->customerCreditCards->first()['id'],
                    'auto_pay'          => '0',
                    'payment_card_no'   => $customer->customerCreditCards->first()['last4'],
                    'payment_card_holder' => $customer->customerCreditCards->first()['cardholder'],
                    'expires_mmyy'      => $customer->customerCreditCards->first()['expiration'],
                    'payment_cvc'       => $customer->customerCreditCards->first()['cvc'],
                    'amount'            => $totalInvoice,
                    'customer_id'       => $order['customer_id'],
                    'order_hash'        => $order['hash'],
                ]));
                $invoiceId = Credit::find($saveInvoice->json()['credit']['id'])->usedOnInvoices->first()->invoice_id;

                $customerStandaloneDevice = $this->withHeaders(self::HEADER_DATA)->post('api/create-device-record?'.http_build_query([
                    'api_key'       => self::HEADER_DATA['Authorization'],
                    'order_id'      => $order['id'],
                    'customer_id'   => $customer['id'],
                    'coupon_data'   => [
                        'code'      => $randomCode,
                        'amount'    => $discount,
                        'description' => 'Coupon for device with id: '.$randomDevice->id,
                    ],
                    'device_id'     => $randomDevice['id'],
                ]));

                $invoiceItems = $this->withHeaders(self::HEADER_DATA)->post('api/generate-one-time-invoice?'.http_build_query(
                [
                    'data_to_invoice'   => ['customer_standalone_device_id' => 
                    [
                        $customerStandaloneDevice->json()['device_id']]
                    ],
                    'customer_id'       => $order['customer_id'],
                    'hash'              => $order['hash'],
                    'order_id'          => $order['id']
                ]));

                return  $customerStandaloneDevice->assertJson(['device_id' => $order->standAloneDevices->first()['id']]) &&
                        $saveInvoice->assertJson(['success' => true]) &&
                        $invoiceItems->assertSeeText('Invoice item generated successfully')->assertJson(
                            [
                                'invoice_items_total' => $totalInvoice
                            ]
                        );

            }
        }
    }

    public function test_plan_with_coupon()
    {
        $customers      = Customer::inRandomOrder()
                            ->whereNotNull('billing_fname')
                            ->whereNotNull('shipping_fname')->get();
        $randomPlan         = Plan::inRandomOrder()->limit(1)->first();
        $hash               = sha1(time());

        foreach ($customers as $customer) {
            
            if (count($customer->customerCreditCards)) {

                $randomCode     = str_random(10);
                Coupon::create([
                            'company_id'    => $customer['company_id'],
                            'active'        => 1,
                            'class'         => 2,
                            'fixed_or_perc' => 2,
                            'amount'        => 40,
                            'code'          => $randomCode,
                            'num_cycles'    => 10,
                            'max_uses'      => 100,
                            'num_uses'      => 1,
                            'stackable'     => 1,
                            'start_date'    => '2019-07-10 18:09:57	',
                            'end_date'      => '2021-07-10 18:09:57	',
                            'multiline_min' => 1,
                            'multiline_max' => 2,
                            'multiline_restrict_plans' => 0
                        ]);
                $coupon = Coupon::where('code', $randomCode)->first();
                CouponProductType::create([
                    'coupon_id' => $coupon['id'],
                    'amount' => 3.00,
                    'type'  => 1,
                    'sub_type' => 0
                ]);
                $storeOrder         = $this->withHeaders(self::HEADER_DATA)->post('api/order?'.http_build_query([
                    'hash' => $hash,
                    'company_id' => $customer->company_id
                ]));
                
                $order = Order::find($storeOrder->json()['id']);
                $updateOrder  = $this->withHeaders(self::HEADER_DATA)->post('api/create-customer?'.http_build_query(
                    [
                        'customer_id'   => $customer['id'],
                        'order_hash'    => $order['hash']
                    ]));

                $order = Order::where('hash', $order['hash'])->first();

                $planAmount         = number_format($order->planProRate($randomPlan['id']), 2);
                $tax                = number_format(isset($customer->stateTax) && $randomPlan['taxable'] ? ($planAmount * $customer->stateTax->rate) / 100 : 0, 2);
                $regulatory         = number_format($randomPlan['regulatory_fee_type'] == 1 ? $randomPlan['regulatory_fee_amount'] : $planAmount * $randomPlan['regulatory_fee_amount'] / 100, 2);
                $discount           = $planAmount * 3 / 100;
        
                $totalInvoice       = $planAmount + $tax + $regulatory + $randomPlan['amount_onetime'] - $discount;

                $saveInvoice  = $this->withHeaders(self::HEADER_DATA)->post('api/charge-new-card?'.http_build_query([
                    'billing_fname'     => $customer['billing_fname'],
                    'billing_lname'     => $customer['billing_lname'],
                    'billing_address1'  => $customer['billing_address1'],
                    'billing_address2'  => $customer['billing_address1'],
                    'billing_city'      => $customer['city'],
                    'billing_state_id'  => $customer['billing_state_id'],
                    'billing_zip'       => $customer['zip'],
                    'coupon'            => NULL,
                    'customer_card'     => $customer->customerCreditCards->first()['id'],
                    'auto_pay'          => '0',
                    'payment_card_no'   => $customer->customerCreditCards->first()['last4'],
                    'payment_card_holder' => $customer->customerCreditCards->first()['cardholder'],
                    'expires_mmyy'      => $customer->customerCreditCards->first()['expiration'],
                    'payment_cvc'       => $customer->customerCreditCards->first()['cvc'],
                    'amount'            => number_format($totalInvoice, 2),
                    'customer_id'       => $order['customer_id'],
                    'order_hash'        => $order['hash'],
                ]));

                $subscription = $this->withHeaders(self::HEADER_DATA)->post('api/create-subscription?'.http_build_query([
                    'api_key'          => self::HEADER_DATA['Authorization'],
                    'order_id'         => $order['id'],
                    'plan_id'          => $randomPlan->id,
                    'sim_num'          => '2314567891234561234',
                    'sim_type'         => 'Sample Name',
                    'coupon_data'   => [
                        'code'      => $randomCode,
                        'amount'    => $discount,
                        'description' => 'Coupon for plan with id: '.$randomPlan->id,
                    ],
                ]));

                $invoiceItems = $this->withHeaders(self::HEADER_DATA)->post('api/generate-one-time-invoice', 
                    [
                        'data_to_invoice'   => ['subscription_id' => [$order->subscriptions->first()['id']]],
                        'customer_id'       => $saveInvoice->json()['card']['card']['customer_id'],
                        'hash'              => $order['hash'],
                        'couponAmount'      => 0,
                        'couponCode'        => NULL,
                        'order_id'          => $order['id']
                    ]);
                
                return  $storeOrder->assertJson(['order_hash' => $order->hash]) &&
                        $subscription->assertJson([
                            'success' => true,
                            'subscription_id' => $order->subscriptions->first()['id']]) && 
                        $invoiceItems->assertSeeText('Invoice item generated successfully')->assertJson(
                                            [
                                                'invoice_items_total' => number_format($totalInvoice, 2)
                                            ]
                                        );

            }
        }
    }

    public function test_get_taxrate()
    {
        $customer      = Customer::inRandomOrder()
                            ->whereNotNull('billing_fname')
                            ->whereNotNull('shipping_fname')->first();
        $taxId = $customer->billing_state_id;
        $rate  = Tax::where('state', $taxId)->first()->rate;
        $getTaxRate = $this->withHeaders(self::HEADER_DATA)->get('api/customer?'.http_build_query([
            'tax_id' => $taxId
        ]));
        return $getTaxRate->assertJson(['tax_rate' =>$rate]);
    }

}
