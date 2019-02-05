<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CustomerTest extends TestCase
{
    /**
     * A basic test example.
     *
     * @return void
     */
    public function testExample()
    {
    	$response = $this->post('/api/create-customer?order_hash=0058f7836a86d7cb60e4017c3f34758b3ce5cd87&shipping_address1=sdlbfhdshfdshb&shipping_address2=sdfdsfs&shipping_city=dsbfhldbsfhsdbfhb&shipping_state_id=HG&email=test1@gmail.com&password=test@123&fname=test1&lname=test1&company_name=test1company&phone=123456&shipping_zip=12345&pin=1234');

        $response->assertStatus(200);
    }

    public function getCustomer()
    {
    	$response = $this->get('/api');

    	$response->assertStatus(200);

    }
}
