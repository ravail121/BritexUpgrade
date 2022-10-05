<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class CustomerCurrentInvoiceTest extends TestCase
{
    use WithFaker;
    use DatabaseTransactions;
    
    const HEADER_DATA = ['Authorization' => 'alar324r23423'];
    /**
     * A basic test example.
     *
     * @return void
     */
    public function testInvoice()
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

        $response = $this->withHeaders(self::HEADER_DATA)->get('/api/customer-current-invoice?hash='.$customer['customer']['hash']);

        $response->assertStatus(200)->assertJson([
            'charges' => true,
        ]);
    }

    public function test_invoice_without_apikey()
    {
    	$response = $this->get('/api/customer-current-invoice?hash=ad76e1663b79b3601f70e4627c0a41c97745c209');

    	$response->assertJson([
            'message' => 'Invalid API Token.',
        ]);
    }
}
