<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class DeviceTest extends TestCase
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

    public function saveDevice()
    {
    	$response =$this->post('/add');
    }

    public function getDevice()
    {
    	$response = $this->get('/devices/1');

    	$response->assertStatus(200);
    }

    public function getAllDevices()
    {
		$response = $this->get('/devices');

		$response->assertStatus(200);
    }
}
