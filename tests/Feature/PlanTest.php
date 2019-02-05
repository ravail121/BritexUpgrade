<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PlanTest extends TestCase
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

    public function getPlan()
    {
    	$response = $this->get('/plan/1');

    	$response->assertStatus(200);
    }

    public function getAllPlans() 
    {
    	$response = $this->get('/plan');

    	$response->assertStatus(200);
    }

}
