<?php

namespace Tests\Feature\Invoice;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Model\Coupon;
use App\Model\Order;
use App\Model\Device;
use App\Model\Customer;
use App\Model\OrderGroup;

class CouponTest extends TestCase
{
    use DatabaseTransactions;
    const HEADER_DATA = ['Authorization' => 'alar324r23423'];
    /**
     * A basic test example.
     *
     * @return void
     */
    public function test_class_1()
    {
        $randomClassOneCoupon   = Coupon::inRandomOrder()->where('class', 1)->where('fixed_or_perc', 1)->limit(1)->first();
        $insertOrder            = $this->withHeaders(self::HEADER_DATA)->post('api/order');
        $order                  = Order::find($insertOrder->json()['id']);
        $randomDevice           = Device::inRandomOrder()->limit(1)->first();
        $customer               = Customer::find(137);
        $orderGroup             = OrderGroup::create([
            'order_id' => $order['id'],
            'device_id' => $randomDevice['id']
        ]);
        $randomDevice['order_group_id'] = $orderGroup['id'];
        $applyCoupon            = $this->withHeaders(self::HEADER_DATA)->post('api/coupon/add-coupon?'.http_build_query(
            [
                'code'      => $randomClassOneCoupon['code'],
                'order_id'  => $order['id'],
                'hash'      => $customer['hash'],
                'orderGroupsCart' => [
                    'devices' => [
                        $randomDevice
                    ]
                ]
            ]
        ));
        return ($applyCoupon->assertJson([
            'total'=>$randomClassOneCoupon['amount']
        ]));
    }
}
