<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CustomerDetailsTest extends TestCase
{
    const HEADER_DATA = ['Authorization' => 'alar324r23423'];

    public function test_customer()
    {
    	$response = $this->withHeaders(self::HEADER_DATA)->get('/api/customer?hash=ad76e1663b79b3601f70e4627c0a41c97745c209');

    	$response->assertStatus(200);
    }

    public function test_customer_without_hash()
    {
    	$response = $this->withHeaders(self::HEADER_DATA)->get('/api/customer');

    	$response->assertJson([
            'error' => 'Hash is required',
        ]);
    }
}
