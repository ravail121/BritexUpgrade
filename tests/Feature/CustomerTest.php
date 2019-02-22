<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CustomerTest extends TestCase
{
    const HEADER_DATA = ['Authorization' => 'alar324r23423'];
    /**
     * A basic test example.
     *
     * @return void
     */
    public function test_create_customer()
    {
    	// $response = $this->post('/api/create-customer?order_hash=0058f7836a86d7cb60e4017c3f34758b3ce5cd87&shipping_address1=sdlbfhdshfdshb&shipping_address2=sdfdsfs&shipping_city=dsbfhldbsfhsdbfhb&shipping_state_id=HG&email=test1@gmail.com&password=test@123&fname=test1&lname=test1&company_name=test1company&phone=123456&shipping_zip=12345&pin=1234');

        // $urlData = [
        //     'order_hash'        => '0058f7836a86d7cb60e4017c3f34758b3ce5cd87',
        //     'shipping_address1' => 'sdlbfhdshfdshb',
        //     'shipping_city'     => 'sdlbfhdshfdshb',
        //     'shipping_address2' => 'sdfdsfs.',
        //     'shipping_state_id' => 'NY',
        //     'email'             => 'test1@gmail.com',
        //     'password'          => 'qwerty',
        //     'fname'             => 'test',
        //     'lname'             => 'test',
        //     'company_name'      => 'test',
        //     'phone'             => '123456',
        //     'shipping_zip'      => '123456',
        //     'pin'               => '1234',
        // ];

        // \Log::info(http_build_query($urlData));
         
        // $response = $this->withHeaders(self::HEADER_DATA)->get('api/create-customer?'.http_build_query($urlData));

        $response = $this->withHeaders(self::HEADER_DATA)->post('/api/create-customer?order_hash=0058f7836a86d7cb60e4017c3f34758b3ce5cd87&shipping_address1=sdlbfhdshfdshb&shipping_address2=sdfdsfs&shipping_city=dsbfhldbsfhsdbfhb&shipping_state_id=HG&email=test1@gmail.com&password=test@123&fname=test1&lname=test1&company_name=test1company&phone=123456&shipping_zip=12345&pin=1234');

        $response->assertStatus(200);
    }

    public function testgetCustomer()
    {
    	$response = $this->get('/api');

    	$response->assertStatus(200);

    }
}
