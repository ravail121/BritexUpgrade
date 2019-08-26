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
        $customer       = Customer::find(137);
        $order          = $this->withHeaders(self::HEADER_DATA)->post('api/order');

        $tax            = isset($customer->stateTax) && $randomDevice['taxable'] ? ($randomDevice['amount'] * $customer->stateTax->rate) / 100 : 1;
        $shipping       = $randomDevice->shipping_fee;
        $totalInvoice   = $randomDevice->amount + $shipping + $tax;
        
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
        
        $invoiceItems = $this->withHeaders(self::HEADER_DATA)->post('api/generate-one-time-invoice', 
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
        ]);
 
        return $invoiceItems->assertStatus(200);
    }

    public function test_sim_only()
    {
        $randomsim      = Sim::inRandomOrder()->limit(1)->first();
        $customer       = Customer::find(137);
        $order          = $this->withHeaders(self::HEADER_DATA)->post('api/order');

        $tax            = isset($customer->stateTax) && $randomsim['taxable'] ? ($randomsim['amount'] * $customer->stateTax->rate) / 100 : 1;
        $shipping       = $randomsim->shipping_fee ?: 0;
        $totalInvoice   = $randomsim->amount_alone + $shipping + $tax;
        
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
 
        return $generateInvoice->assertStatus(200);

    }

    public function test_plan_only()
    {
        $customer           = Customer::find(137);
        $randomPlan         = Plan::inRandomOrder()->limit(1)->first();

        $storeOrder         = $this->withHeaders(self::HEADER_DATA)->post('api/order?'.http_build_query([
            'hash' => sha1(time()),
            'company_id' => $customer->company_id
        ]));

        //Need to upate order manually to add customer_id because it gets added from frontend.
        $order = Order::find($storeOrder->json()['id']);
        $order->update(['customer_id' => $customer['id']]);

        $planAmount         = $order->planProRate($randomPlan['id']);
        $tax                = isset($customer->stateTax) && $randomPlan['taxable'] ? ($planAmount * $customer->stateTax->rate) / 100 : 1;
        $regulatory         = $randomPlan['regulatory_fee_type'] == 1 ? $randomPlan['regulatory'] : $planAmount * $randomPlan['regulatory_fee_amount'] / 100;
        
        $totalInvoice       = $planAmount + $tax + $regulatory;

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
        
        return $subscription->assertJson([
            'success' => true,
            'subscription_id' => $order->subscriptions->first()['id']
        ]) && $invoiceItems->assertStatus(200);
    }

    public function test_complete_subscription()
    {
        $customer           = Customer::find(137);
        $randomPlan         = Plan::inRandomOrder()->limit(1)->first();
        $randomSim          = Sim::inRandomOrder()->limit(1)->first();
        $randomDevice       = Device::inRandomOrder()->limit(1)->first();
        $randomAddon        = Addon::inRandomOrder()->limit(1)->first();

        $storeOrder         = $this->withHeaders(self::HEADER_DATA)->post('api/order?'.http_build_query([
            'hash' => sha1(time()),
            'company_id' => $customer->company_id
        ]));

        $order = Order::find($storeOrder->json()['id']);
        $order->update(['customer_id' => $customer['id']]);

        $planAmount         = $order->planProRate($randomPlan['id']);
        $addonAmount        = $order->addonProRate($randomAddon['id']);
        $tax                = isset($customer->stateTax) && $randomPlan['taxable'] ? ($customer->stateTax->rate / 100) : 1;
        $taxableAmount      = [
            $randomPlan['taxable'] ? $planAmount : 0,
            $randomDevice['taxable'] ? $randomDevice['amount_w_plan'] : 0,
            $randomSim['taxable'] ? $randomSim['amount_w_plan'] : 0,
            $randomAddon['taxable'] ? $randomAddon['amount_recurring'] : 0
        ];
        $totalShipping      = [
            $randomDevice['shipping_fee'] ?: 0, $randomSim['shipping_fee'] ?: 0
        ];
        $regulatory         = $randomPlan['regulatory_fee_type'] == 1 ? $randomPlan['regulatory_fee_amount'] : $planAmount * $randomPlan['regulatory_fee_amount'] / 100;
        $device             = $randomDevice['amount_w_plan'];
        $sim                = $randomSim['amount_w_plan'];
        $total              = ($device + $sim + $planAmount + $regulatory + $addonAmount + array_sum($totalShipping)) + (array_sum($taxableAmount) * $tax);
        
        $subscription = $this->withHeaders(self::HEADER_DATA)->post('api/create-subscription?'.http_build_query([
            'api_key'   => self::HEADER_DATA['Authorization'],
            'order_id'  => $order['id'],
            'device_id' => $randomDevice['id'],
            'plan_id'   => $randomPlan['id'],
            'sim_id'    => $randomSim['id'],
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
                'subscription_addon_id' => [$order->subscriptions->first()->subscriptionAddon->first()['id']]
            ],
            'customer_id'       => $saveInvoice->json()['card']['card']['customer_id'],
            'hash'              => $order['hash'],
            'couponAmount'      => 0,
            'couponCode'        => NULL,
            'order_id'          => $order['id']
        ]);
        return $subscription->assertJson([
            'success' => true,
            'subscription_id' => $order->subscriptions->first()['id']
        ]) && $invoiceItems->assertStatus(200);
    }

}
