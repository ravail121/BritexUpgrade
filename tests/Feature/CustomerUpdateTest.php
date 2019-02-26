<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class CustomerUpdateTest extends TestCase
{
    use WithFaker;
    use DatabaseTransactions;

	const HEADER_DATA = ['Authorization' => 'alar324r23423'];
    /**
     * A basic test example.
     *
     * @return void
     */
    public function test_update()
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
            'shipping_zip'      => '10001',
            'pin'               => '1234'
        ];

        $customerResponse = $this->withHeaders(self::HEADER_DATA)->post('api/create-customer?'.http_build_query($customerData));
        $customer =  $customerResponse->json();

    	$urlData = [
    		'id'			=> $customer['customer']['id'],
    		'hash'	   		=> $customer['customer']['hash'],
    		'fname' 		=> $this->faker->firstName(),
    		'lname' 		=> $this->faker->lastName(),
    	];

        $response = $this->withHeaders(self::HEADER_DATA)->post('api/update-customer?'.http_build_query($urlData));

        $response->assertJson([
            'message' => 'sucessfully Updated',
        ]);
    }

    public function test_update_without_data()
    {
    	$urlData = [];

        $response = $this->withHeaders(self::HEADER_DATA)->post('api/update-customer?'.http_build_query($urlData));

        $response->assertStatus(500);
    }

    public function test_data_without_id_and_hash()
    {

    	$urlData = [
    		'hash'	=> 'ad76e1663b79b3601f70e4627c0a41c97745c209',
    		'fname' => $this->faker->firstName(),
            'lname' => $this->faker->lastName(),
    	];

        $response = $this->withHeaders(self::HEADER_DATA)->post('api/update-customer?'.http_build_query($urlData));

        $response->assertStatus(500);
    }
}

