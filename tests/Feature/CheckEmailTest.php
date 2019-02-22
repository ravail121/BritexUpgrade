<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CheckEmailTest extends TestCase
{
	const HEADER_DATA = ['Authorization' => 'alar324r23423'];
    /**
     * A basic test example.
     *
     * @return void
     */
    public function test_email()
    {
    	$urlData = [
    		'hash' => 'ad76e1663b79b3601f70e4627c0a41c97745c209',
    		'newEmail' => 'testnew@test.com'
    	];

        $response = $this->withHeaders(self::HEADER_DATA)->get('api/check-email?'.http_build_query($urlData));

    	$response->assertStatus(200);
    }

    public function test_cheak_email_without_email()
    {
    	$urlData = [
    		'hash' => 'ad76e1663b79b3601f70e4627c0a41c97745c209'
    	];

        $response = $this->withHeaders(self::HEADER_DATA)->get('api/check-email?'.http_build_query($urlData));

    	$response->assertStatus(302);
    }

    public function test_cheak_email_without_hash()
    {
    	$urlData = [
    		'newEmail' => 'testnew@test.com'
    	];

        $response = $this->withHeaders(self::HEADER_DATA)->get('api/check-email?'.http_build_query($urlData));

    	$response->assertStatus(302);
    }
}
