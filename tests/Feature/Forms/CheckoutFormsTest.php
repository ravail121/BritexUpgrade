<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Model\Tax;
use App\Model\Customer;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Model\Order;
use App\Model\Plan;

class CheckoutFormsTest extends TestCase
{
    const HEADER_DATA = ['Authorization' => 'alar324r23423'];
    use WithFaker;
    // use DatabaseTransactions;
    public $customerId;

    public function test_update_billing_details()
    {   
        $customer = Customer::inRandomOrder()
            ->whereNotNull('billing_fname')
            ->whereNotNull('shipping_fname')->first();
        $randomTaxId = Tax::inRandomOrder()->first()->state;
        $saveBillingDetails = $this->withHeaders(self::HEADER_DATA)->post('api/create-customer?'.http_build_query([
            'billing_state_id'  => $randomTaxId,
            'billing_fname'     => 'Billing fname',
            'billing_lname'     => 'Billing lname',
            'billing_address1'  => 'Billing address 1',
            'billing_address2'  => 'Billing address 2',
            'billing_city'      => 'Billing city',
            'billing_zip'       => '44556',
            'id' => $customer->id
        ]));
        $saveBillingDetails->assertJson(
            [
                'success' => true,
                'id'    => $randomTaxId
            ]
        );    
    }

    public function test_update_billing_details_empty()
    {   
        $customer = Customer::inRandomOrder()
            ->whereNotNull('billing_fname')
            ->whereNotNull('shipping_fname')->first();
        $randomTaxId = Tax::inRandomOrder()->first()->state;
        $saveBillingDetails = $this->withHeaders(self::HEADER_DATA)->post('api/create-customer?'.http_build_query([
            'billing_state_id'  => $randomTaxId,
            'billing_fname'     => '',
            'billing_lname'     => '',
            'billing_address1'  => '',
            'billing_address2'  => '',
            'billing_city'      => '',
            'billing_zip'       => '',
            'id' => $customer->id
        ]));
        $saveBillingDetails->assertStatus(302);
    }



    public function test_update_shipping_info()
    {
        $customer = Customer::inRandomOrder()
                    ->whereNotNull('billing_fname')
                    ->whereNotNull('shipping_fname')->first();
        $updateShippingDetails = $this->withHeaders(self::HEADER_DATA)->post('api/update-customer?'.http_build_query([
            'shipping_address1' => 'Shipping address 1',
            'shipping_fname' => 'Shipping fname',
            'shipping_lname' => 'Shipping lname',
            'shipping_city' => 'Shipping city',
            'shipping_zip' => '12345',
            'shipping_state_id' => 'AZ',
            'shipping_address2' => 'Shipping address 2',
            'id' => '137',
            'hash' => 'e29054818f4362de0b29456ae3bf928353f2fbeb',
        ]));
        $updateShippingDetails->assertJson(
            [
                'message' => 'sucessfully Updated'
            ]
        );
    }

    public function test_update_shipping_info_empty()
    {
        $customer = Customer::inRandomOrder()
                    ->whereNotNull('billing_fname')
                    ->whereNotNull('shipping_fname')->first();
        $updateShippingDetails = $this->withHeaders(self::HEADER_DATA)->post('api/update-customer?'.http_build_query([
            'shipping_address1' => '',
            'shipping_fname' => '',
            'shipping_lname' => '',
            'shipping_city' => '',
            'shipping_zip' => '',
            'shipping_state_id' => '',
            'shipping_address2' => '',
            'id' => $customer->id,
            'hash' => '',
        ]));
        $updateShippingDetails->assertJson(
            [
                'details' => [
                    "The hash field is required.",
                    "The shipping address1 field is required.",
                    "The shipping city field is required.",
                    "The shipping zip field is required."
                ]
            ]
        );
    }

    public function test_customer()
    {
        $customerData = [
            'order_hash'        => '0058f7836a86d7cb60e4017c3f34758b3ce5cd87',
            'shipping_address1' => $this->faker->streetAddress(),
            'shipping_city'     => $this->faker->address(),
            'shipping_address2' => $this->faker->streetAddress(),
            'shipping_state_id' => 'HG',
            'email'             => $this->faker->email(),
            'password'          => 'qwerty',
            'fname'             => $this->faker->firstName(),
            'lname'             => $this->faker->lastName(),
            'company_name'      => $this->faker->company(),
            'phone'             => $this->faker->phoneNumber(),
            'shipping_zip'      => rand(10000,12000),
            'pin'               => rand(1000,1200)
        ];

        $customerResponse = $this->withHeaders(self::HEADER_DATA)->post('api/create-customer?'.http_build_query($customerData));
        $customer =  $customerResponse->json();

    	$response = $this->withHeaders(self::HEADER_DATA)->get('/api/customer?hash='.$customer['customer']['hash']);

        $response->assertStatus(200)->assertJson([
            'id' => true,
        ]);

    }

    public function test_customer_without_hash()
    {
    	$response = $this->withHeaders(self::HEADER_DATA)->get('/api/customer');

    	$response->assertJson([
            'error' => 'Hash is required',
        ]);
    }

    public function test_add_card()
    {
    	$customerData = [
            'order_hash'        => '0058f7836a86d7cb60e4017c3f34758b3ce5cd87',
            'shipping_address1' => $this->faker->streetAddress(),
            'shipping_city'     => $this->faker->address(),
            'shipping_address2' => $this->faker->streetAddress(),
            'shipping_state_id' => 'HG',
            'email'             => $this->faker->email(),
            'password'          => 'qwerty',
            'fname'             => $this->faker->firstName(),
            'lname'             => $this->faker->lastName(),
            'company_name'      => $this->faker->company(),
            'phone'             => $this->faker->phoneNumber(),
            'shipping_zip'      => rand(10000,12000),
            'pin'               => rand(1000,1200)
        ];

        $response = $this->withHeaders(self::HEADER_DATA)->post('api/create-customer?'.http_build_query($customerData));
        $customer =  $response->json();
        $this->customerId = $customer['customer']['id'];

    	$urlData = [
    		'billing_fname' 		 => $this->faker->firstName(),
    		'billing_lname' 		 => $this->faker->lastName(),
    		'payment_card_no'        => '4111111111111111',
	        'expires_mmyy'           => $this->faker->creditCardExpirationDateString(),
	        'payment_cvc'            => '123',
	        'payment_card_holder'    => $this->faker->firstName(),
	        'billing_address1'       => $this->faker->streetAddress(),
	        'billing_city'           => $this->faker->streetAddress(),
	        'billing_state_id'       => 'NY',
	        'billing_zip'            => '10001',
	        'customer_id'			 => $this->customerId
    	];

        $cardResponse = $this->withHeaders(self::HEADER_DATA)->post('api/add-card?'.http_build_query($urlData));

        $cardResponse->assertStatus(200)->assertJson([
            'success' => 'true',
        ]);
    }

    public function test_add_card_without_payment_card_no()
    {
    	$urlData = [
    		'billing_fname' 		 => $this->faker->firstName(),
    		'billing_lname' 		 => $this->faker->lastName(),
	        'expires_mmyy'           => $this->faker->creditCardExpirationDateString(),
	        'payment_cvc'            => '452',
	        'payment_card_holder'    => $this->faker->firstName(),
	        'billing_address1'       => $this->faker->streetAddress(),
	        'billing_city'           => $this->faker->streetAddress(),
	        'billing_state_id'       => 'NY',
	        'billing_zip'            => '10001',
	        'customer_id'			 => $this->customerId
    	];

        $response = $this->withHeaders(self::HEADER_DATA)->post('api/add-card?'.http_build_query($urlData));

        $response->assertStatus(500);
    }

    public function test_sim_number_edit()
    {
        $customer   = Customer::inRandomOrder()
                            ->whereNotNull('billing_fname')
                            ->whereNotNull('shipping_fname')->first();
        $randomPlan = Plan::inRandomOrder()->where('sim_required', 1)->limit(1)->first();
        $insertOrder = $this->withHeaders(self::HEADER_DATA)->post('api/order');
        $order       = Order::find($insertOrder->json()['id']);
        $newSimNumber = '7896541230';
        $updateOrder = $this->withHeaders(self::HEADER_DATA)->post('api/order?'.http_build_query([
            'plan_in' => $randomPlan['id'],
            'sim_id'  => 0,
            'order_hash' => $order['hash'],
            'sim_type' => 'T-MOBILE MICRO',
            'sim_num' => '1234561234561234561',
            'sim_required' => $randomPlan['sim_required'],
            'customer_hash' => $customer['hash']
        ]));
        $editSim = $this->withHeaders(self::HEADER_DATA)->post('api/order-group/edit?'.http_build_query([
            'newSimNumber'  => $newSimNumber,
            'orderGroupId'  => $order->allOrderGroup->first()->id
        ]));
        $editSim->assertJson(
            [
                'new_sim_num' => $newSimNumber
            ]
        );
    }
    
}
