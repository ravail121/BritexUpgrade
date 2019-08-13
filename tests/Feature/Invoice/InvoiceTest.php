<?php

namespace Tests\Feature\Invoice;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Model\Device;
use App\Model\Customer;
use App\Model\Sim;
use App\Model\Plan;

class DeviceOnlyTest extends TestCase
{
    use WithFaker;
    use DatabaseTransactions;

	const HEADER_DATA = ['Authorization' => 'alar324r23423'];
    /**
     * A basic test example.
     *
     * @return void
     */

    public function test_device_only()
    {
        $randomDevice   = Device::inRandomOrder()->limit(1)->first();
        $randomCustomer = Customer::inRandomOrder()->limit(1)->first();
        
        $order          = $this->withHeaders(self::HEADER_DATA)->post('api/order?'.http_build_query([
            'hash' => sha1(time()),
            'company_id' => $randomCustomer->company_id
        ]));
        
        $orderResponse  = $order->json();
        
        $customerStandaloneDevice = $this->withHeaders(self::HEADER_DATA)->post('api/create-device-record?'.http_build_query([
            'api_key'       => self::HEADER_DATA['Authorization'],
            'order_id'      => $orderResponse['id'],
            'device_id'     => $randomDevice->id,
            'customer_id'   => $randomCustomer->id
        ]));

        return $customerStandaloneDevice->assertJson(
            [
                'device_id' => true
            ]
        )->assertStatus(200);
        
    }

    public function test_sim_only()
    {
        $randomSim        = Sim::inRandomOrder()->limit(1)->first();
        $randomCustomer   = Customer::inRandomOrder()->limit(1)->first();
        
        $order = $this->withHeaders(self::HEADER_DATA)->post('api/order?'.http_build_query([
            'hash' => sha1(time()),
            'company_id' => $randomCustomer->company_id
        ]));

        $orderResponse  = $order->json();
        
        $customerStandaloneDevice = $this->withHeaders(self::HEADER_DATA)->post('api/create-sim-record?'.http_build_query([
            'api_key'       => self::HEADER_DATA['Authorization'],
            'customer_id'   => $randomCustomer->id,
            'order_id'      => $orderResponse['id'],
            'order_num'     => NULL,
            'status'        => 'shipping',
            'processed'     => 0,
            'sim_id'        => $randomSim->id
        ]));

        return $customerStandaloneDevice->assertJson(
            [
                'sim_id' => true
            ]
        )->assertStatus(200);

    }

    public function test_plan_only()
    {
        $randomSim        = Sim::inRandomOrder()->limit(1)->first();
        $randomCustomer   = Customer::inRandomOrder()->limit(1)->first();
        $randomPlan       = Plan::inRandomOrder()->limit(1)->first();

        $order = $this->withHeaders(self::HEADER_DATA)->post('api/order?'.http_build_query([
            'hash' => sha1(time()),
            'company_id' => $randomCustomer->company_id
        ]));

        $subscription = $this->withHeaders(self::HEADER_DATA)->post('api/create-subscription?'.http_build_query([
            'api_key'          => self::HEADER_DATA['Authorization'],
            'order_id'         => $order->json()['id'],
            'plan_id'          => $randomPlan->id,
            'sim_id'           => $randomSim->id,
        ]));
        
        return $subscription->assertJson([
            'success' => true,
            'subscription_id' => true
        ])->assertStatus(200);
    }

}
