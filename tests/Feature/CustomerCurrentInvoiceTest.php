<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CustomerCurrentInvoiceTest extends TestCase
{

    const HEADER_DATA = ['Authorization' => 'alar324r23423'];
    /**
     * A basic test example.
     *
     * @return void
     */
    public function testInvoice()
    {
        $response = $this->withHeaders(self::HEADER_DATA)->get('/api/customer-current-invoice?hash=ad76e1663b79b3601f70e4627c0a41c97745c209');

    	$response->assertStatus(200);
    }

    public function test_invoice_without_apikey()
    {
    	$response = $this->get('/api/customer-current-invoice?hash=ad76e1663b79b3601f70e4627c0a41c97745c209');

    	$response->assertJson([
            'message' => 'Invalid API Token.',
        ]);
    }
}
