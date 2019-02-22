<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CardTest extends TestCase
{
    /**
     * A basic test example.
     *
     * @return void
     */
    public function testExample()
    {
        $this->assertTrue(true);
    }

    public static $Headerdata = ['Authorization' => 'alar324r23423'];

    public function testgetCard()
    {
        $response = $this->withHeaders(self::$Headerdata)->get('/api/customer-cards?customer_id=112');

    	$response->assertStatus(200);
    }

    public function test_get_card_without_customer_id()
    {
        $response = $this->withHeaders(self::$Headerdata)->get('/api/customer-cards');

        $response->assertJson([
            'message' => 'CustomerId or customer_hash request',
        ]);
    }
}
