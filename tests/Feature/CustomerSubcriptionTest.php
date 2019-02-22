<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CustomerSubcriptionTest extends TestCase
{
	const HEADER_DATA = ['Authorization' => 'alar324r23423'];
    /**
     * A basic test example.
     *
     * @return void
     */
    public function testSubcription()
    {
    	$response = $this->withHeaders(self::HEADER_DATA)->get('api/customer-subscriptions?hash=ad76e1663b79b3601f70e4627c0a41c97745c209');

    	$response->assertStatus(200);
    }

    public function test_customer_without_hash()
    {
    	$response = $this->withHeaders(self::HEADER_DATA)->get('/api/customer-subscriptions');

    	$response->assertStatus(302);
    }
}

