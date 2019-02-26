<?php

namespace Tests\Feature;

use Config;
use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class PrimaryCardTest extends TestCase
{
    use WithFaker;
    use DatabaseTransactions;

    const HEADER_DATA = ['Authorization' => 'alar324r23423'];
    
    public function test_primary_card()
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
        $this->customerId = $customer['customer']['id'];

        $cardData = [
            'billing_fname'          => $this->faker->firstName(),
            'billing_lname'          => $this->faker->lastName(),
            'payment_card_no'        => '4111111111111111',
            'expires_mmyy'           => $this->faker->creditCardExpirationDateString(),
            'payment_cvc'            => '123',
            'payment_card_holder'    => $this->faker->firstName(),
            'billing_address1'       => $this->faker->streetAddress(),
            'billing_city'           => $this->faker->streetAddress(),
            'billing_state_id'       => 'NY',
            'billing_zip'            => rand(10000,12000),
            'customer_id'            => $this->customerId
        ];

        $cardResponse = $this->withHeaders(self::HEADER_DATA)->post('api/add-card?'.http_build_query($cardData));
        $card =  $cardResponse->json();
    	
        $response = $this->withHeaders(self::HEADER_DATA)->post('/api/primary-card?customer_credit_card_id='.$card['card']['id'].'&id='.$customer['customer']['id']);

        $response->assertJson([
            'details' => 'Card Sucessfully Updated' ]);
    }

    public function test_primary_card_without_card_id()
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
            'pin'               => '1234'
        ];

        $customerResponse = $this->withHeaders(self::HEADER_DATA)->post('api/create-customer?'.http_build_query($customerData));
        $customer =  $customerResponse->json();

    	$response = $this->withHeaders(self::HEADER_DATA)->post('/api/primary-card?id='.$customer['customer']['id']);

    	$response->assertStatus(302);
    }

    public function test_primary_card_without_customerid()
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
            'pin'               => '1234'
        ];

        $customerResponse = $this->withHeaders(self::HEADER_DATA)->post('api/create-customer?'.http_build_query($customerData));
        $customer =  $customerResponse->json();
        $this->customerId = $customer['customer']['id'];

        $cardData = [
            'billing_fname'          => $this->faker->firstName(),
            'billing_lname'          => $this->faker->lastName(),
            'payment_card_no'        => '4111111111111111',
            'expires_mmyy'           => $this->faker->creditCardExpirationDateString(),
            'payment_cvc'            => '123',
            'payment_card_holder'    => $this->faker->firstName(),
            'billing_address1'       => $this->faker->streetAddress(),
            'billing_city'           => $this->faker->streetAddress(),
            'billing_state_id'       => 'NY',
            'billing_zip'            => rand(10000,12000),
            'customer_id'            => $this->customerId
        ];

        $cardResponse = $this->withHeaders(self::HEADER_DATA)->post('api/add-card?'.http_build_query($cardData));
        $card =  $cardResponse->json();
        
        $response = $this->withHeaders(self::HEADER_DATA)->post('/api/primary-card?customer_credit_card_id='.$card['card']['id']);

    	$response->assertStatus(302);
    }

    public function test_primary_card_with_non_existing_card_id()
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
            'pin'               => '1234'
        ];

        $customerResponse = $this->withHeaders(self::HEADER_DATA)->post('api/create-customer?'.http_build_query($customerData));
        $customer =  $customerResponse->json();

        $response = $this->withHeaders(self::HEADER_DATA)->post('/api/primary-card?customer_credit_card_id=10001'.'&id='.$customer['customer']['id']);

        $response->assertJson([
            'details' => 'Card Not Found'
        ]);
    }
}
