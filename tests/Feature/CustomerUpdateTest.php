<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CustomerUpdateTest extends TestCase
{
    /**
     * A basic test example.
     *
     * @return void
     */
    public function testUpdate()
    {
        $response = $this->post('/api/update-customer?api_key=alar324r23423&hash=ad76e1663b79b3601f70e4627c0a41c97745c209&email=test1@gmail.com&password=test@123&fname=test1&lname=test1&company_name=test1company&phone=123456&shipping_zip=12345&pin=1234');

        $response->assertStatus(200);
    }
}
