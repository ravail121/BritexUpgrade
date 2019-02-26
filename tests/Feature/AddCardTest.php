<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class AddCardTest extends TestCase
{
	use WithFaker;
    use DatabaseTransactions;
    
    const HEADER_DATA = ['Authorization' => 'alar324r23423'];
    public $customerId; 
    
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
	        'payment_cvc'            => '123',
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
}
