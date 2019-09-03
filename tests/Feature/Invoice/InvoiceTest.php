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
use Illuminate\Support\Str;

class InvoiceTests extends TestCase
{
    use WithFaker;
    use DatabaseTransactions;

	const HEADER_DATA = ['Authorization' => 'alar324r23423'];
    /**
     * A basic test example.
     *
     * @return void
     */

    public function test_device_only()
    {
        $randomDevice   = Device::inRandomOrder()->limit(1)->first();
        $customers      = Customer::inRandomOrder()
                            ->whereNotNull('billing_fname')
                            ->whereNotNull('shipping_fname')->get();
        $order          = $this->withHeaders(self::HEADER_DATA)->post('api/order');

        foreach ($customers as $customer) {
            
            if (count($customer->customerCreditCards)) {

                $tax            = isset($customer->stateTax) && $randomDevice['taxable'] ? ($randomDevice['amount'] * $customer->stateTax->rate) / 100 : 0;
                $shipping       = $randomDevice['shipping_fee'];
                $totalInvoice   = $randomDevice['amount'] + $shipping + $tax;
                
                $customerStandaloneDevice = $this->withHeaders(self::HEADER_DATA)->post('api/create-device-record?'.http_build_query([
                    'api_key'       => self::HEADER_DATA['Authorization'],
                    'order_id'      => $order->json()['id'],
                    'device_id'     => $randomDevice['id'],
                    'customer_id'   => $customer['id']
                ]));

                //Need to upate order manually to add customer_id because it gets added from frontend.
                $updatedOrder = Order::find($order->json()['id']);
                $updatedOrder->update(['customer_id' => $customer['id']]);
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
                    'customer_id'       => $updatedOrder['customer_id'],
                    'order_hash'        => $updatedOrder['hash'],
                ]));
            
                $invoiceItems = $this->withHeaders(self::HEADER_DATA)->post('api/generate-one-time-invoice?'.http_build_query(
                [
                    'data_to_invoice'   => ['customer_standalone_device_id' => 
                    [
                        $customerStandaloneDevice->json()['device_id']]
                    ],
                    'customer_id'       => $updatedOrder['customer_id'],
                    'hash'              => $updatedOrder['hash'],
                    'couponAmount'      => 0,
                    'couponCode'        => NULL,
                    'order_id'          => $updatedOrder['id']
                ]));

                return  $customerStandaloneDevice->assertJson(['device_id' => $updatedOrder->standAloneDevices->first()['id']]) &&
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
        $order          = $this->withHeaders(self::HEADER_DATA)->post('api/order');

        foreach ($customers as $customer) {
            
            if (count($customer->customerCreditCards)) {

                $tax            = isset($customer->stateTax) && $randomsim['taxable'] ? ($randomsim['amount_alone'] * $customer->stateTax->rate) / 100 : 0;
                $shipping       = $randomsim['shipping_fee'] ?: 0;
                $totalInvoice   = $randomsim['amount_alone'] + $shipping + $tax;
                
                $customerStandalonesim = $this->withHeaders(self::HEADER_DATA)->post('api/create-sim-record?'.http_build_query([
                    'api_key'       => self::HEADER_DATA['Authorization'],
                    'order_id'      => $order->json()['id'],
                    'sim_id'        => $randomsim['id'],
                    'customer_id'   => $customer['id']
                ]));
                
                //Need to upate order manually to add customer_id because it gets added from frontend.
                $updatedOrder = Order::find($order->json()['id']);
                $updatedOrder->update(['customer_id' => $customer['id']]);

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
                    'customer_id'       => $updatedOrder['customer_id'],
                    'order_hash'        => $updatedOrder['hash'],
                ]));
            
                $generateInvoice = $this->withHeaders(self::HEADER_DATA)->post('api/generate-one-time-invoice', [
                    'data_to_invoice'   => ['customer_standalone_sim_id' => [$customerStandalonesim->json()['sim_id']]],
                    'customer_id'       => $updatedOrder['customer_id'],
                    'hash'              => $updatedOrder['hash'],
                    'couponAmount'      => 0,
                    'couponCode'        => NULL,
                    'order_id'          => $updatedOrder['id']
                ]);
                
                return  $customerStandalonesim->assertJson(['sim_id' => $updatedOrder->standAloneSims->first()['id']]) &&
                        $saveInvoice->assertJson(['success' => true]) &&
                        $generateInvoice->assertSeeText('Invoice item generated successfully')->assertJson(
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
        $randomPlan         = Plan::inRandomOrder()->limit(1)->first();
        $hash               = sha1(time());

        foreach ($customers as $customer) {
            
            if (count($customer->customerCreditCards)) {

                $storeOrder         = $this->withHeaders(self::HEADER_DATA)->post('api/order?'.http_build_query([
                    'hash' => $hash,
                    'company_id' => $customer->company_id
                ]));
                
                //Need to upate order manually to add customer_id because it gets added from frontend.
                $order = Order::find($storeOrder->json()['id']);
                $order->update(['customer_id' => $customer['id']]);

                $planAmount         = $order->planProRate($randomPlan['id']);
                $tax                = isset($customer->stateTax) && $randomPlan['taxable'] ? ($planAmount * $customer->stateTax->rate) / 100 : 0;
                $regulatory         = $randomPlan['regulatory_fee_type'] == 1 ? $randomPlan['regulatory_fee_amount'] : $planAmount * $randomPlan['regulatory_fee_amount'] / 100;
        
                $totalInvoice       = $planAmount + $tax + $regulatory + $randomPlan['amount_onetime'];

                $subscription = $this->withHeaders(self::HEADER_DATA)->post('api/create-subscription?'.http_build_query([
                    'api_key'          => self::HEADER_DATA['Authorization'],
                    'order_id'         => $order['id'],
                    'plan_id'          => $randomPlan->id,
                    'sim_num'          => '2314567891234561234',
                    'sim_type'         => 'Sample Name',
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

                $invoiceItems = $this->withHeaders(self::HEADER_DATA)->post('api/generate-one-time-invoice', 
                    [
                        'data_to_invoice'   => ['subscription_id' => [$order->subscriptions->first()['id']]],
                        'customer_id'       => $saveInvoice->json()['card']['card']['customer_id'],
                        'hash'              => $order['hash'],
                        'couponAmount'      => 0,
                        'couponCode'        => NULL,
                        'order_id'          => $order['id']
                    ]);
                
                return  $storeOrder->assertJson(['order_hash' => $hash]) &&
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
        $randomPlan         = Plan::inRandomOrder()->limit(1)->first();
        $randomSim          = Sim::inRandomOrder()->limit(1)->first();
        $randomDevice       = Device::inRandomOrder()->limit(1)->first();
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
        
                return  $storeOrder->assertJson(['order_hash' => $hash]) &&
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
                    'amount'            => number_format($total, 2),
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
        
                return  $storeOrder->assertJson(['order_hash' => $hash]) &&
                        $subscription->assertJson([
                            'success' => true,
                            'subscription_id' => $order->subscriptions->first()['id']]) && 
                        $subscriptionAddon->assertJson(['subscription_addon_id' => $order->subscriptions->first()->subscriptionAddon->first()['id']]) &&
                        $saveInvoice->assertJson(['success' => true]) &&
                        $invoiceItems->assertSeeText('Invoice item generated successfully')->assertJson(['invoice_items_total' => number_format($total, 2)]);

            }
        }
    }

}
