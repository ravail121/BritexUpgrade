<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class CardTest extends TestCase
{

    use WithFaker;
    use DatabaseTransactions;

    const HEADER_DATA = ['Authorization' => 'alar324r23423'];
    public $customerId;

    public function testgetCard()
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

        $this->withHeaders(self::HEADER_DATA)->post('api/add-card?'.http_build_query($cardData));

        $getCardResponse = $this->withHeaders(self::HEADER_DATA)->get('/api/customer-cards?customer_id='.$this->customerId);

        $getCardResponse->assertStatus(200);
    }

    public function test_get_card_without_customer_id()
    {
        $response = $this->withHeaders(self::HEADER_DATA)->get('/api/customer-cards');

        $response->assertJson([
            'message' => 'CustomerId or customer_hash request',
        ]);
    }
}
