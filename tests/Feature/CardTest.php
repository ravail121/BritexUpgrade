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

    public function getCard()
    {
    	$response = $this->get('/api/customer-cards?customer_id=97&api_key=alar324r23423');

    	$response->assertStatus(200);
    }
}
