<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class SignOnTest extends TestCase
{
    /**
     * A basic test example.
     *
     * @return void
     */
    public function testExample()
    {
      	$response = $this->post('/api/sign-on?email=10&password=Craft@2017');

        //$response->assertRedirect('/home');
        $response->assertStatus(200);
    }
}
