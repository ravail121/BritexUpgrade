<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class CustomerTest extends TestCase
{
    use WithFaker;
    use DatabaseTransactions;

    const HEADER_DATA = ['Authorization' => 'alar324r23423'];
    /**
     * A basic test example.
     *
     * @return void
     */
    public function test_create_customer()
    {
        $urlData = [
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
         
        $response = $this->withHeaders(self::HEADER_DATA)->post('api/create-customer?'.http_build_query($urlData));

        $response->assertStatus(200)->assertJson([
            'success' => 'true',
        ]);
    }

    public function testgetCustomer()
    {
    	$response = $this->get('/api');

    	$response->assertStatus(200);

    }

    public function test_create_customer_without_data()
    {
        $urlData = [ ];
         
        $response = $this->withHeaders(self::HEADER_DATA)->post('api/create-customer?'.http_build_query($urlData));

        $response->assertStatus(400);
    }

    public function testcreate_customer_without_api_key()
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

        $customerResponse = $this->post('api/create-customer?'.http_build_query($customerData));
        
        $customerResponse->assertJson([
            'message' => 'Invalid API Token.',
        ]);
    }
}
