<?php

namespace Tests\Feature\Invoice;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Model\Coupon;
use App\Model\CouponProductType;
use App\Model\Order;
use App\Model\Device;
use App\Model\Plan;
use App\Model\Addon;
use App\Model\Customer;
use App\Model\OrderGroup;
use App\Model\OrderGroupAddon;

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
                        'items' => [ $randomDevice ]
                    ]
                ]
            ]
        ));
        return ($applyCoupon->assertJson([
            'total'=>$randomClassOneCoupon['amount'],
            'code'  => $randomClassOneCoupon['code'],
            'applied_to' => [
                'applied_to_all' => false,
                'applied_to_types' => false,
                'applied_to_products' => true
            ]
        ]));
    }

    public function test_class_2()
    {
        
        $randomClassTwoCoupon   = Coupon::find(4);
        $couponProductTypes     = $randomClassTwoCoupon->couponProductTypes->first();
        $insertOrder            = $this->withHeaders(self::HEADER_DATA)->post('api/order');
        $order                  = Order::find($insertOrder->json()['id']);
        $randomPlan             = Plan::inRandomOrder()->where('type', 1)->limit(1)->first();
        $customer               = Customer::find(137);
        $orderGroup             = OrderGroup::create([
            'order_id' => $order['id'], 
            'plan_id' => $randomPlan['id'],
        ]);

        $order->update([
            'customer_id' => $customer['id']
        ]);

        $applyCoupon            = $this->withHeaders(self::HEADER_DATA)->post('api/coupon/add-coupon?'.http_build_query(
            [
                'code'      => $randomClassTwoCoupon['code'],
                'order_id'  => $order['id'],
                'hash'      => $customer['hash'],
                'orderGroupsCart' => [
                    'plans' =>[ 
                        'items' => [$randomPlan->toArray()]
                    ]
                ]
            ]
        ));

        return ($applyCoupon->assertJson([
            'total' => $couponProductTypes['amount'],
            'code'  => $randomClassTwoCoupon['code'],
            'applied_to' => [
                'applied_to_all' => false,
                'applied_to_types' => true,
                'applied_to_products' => false
            ]
        ]));
    }


    public function test_class_3()
    {
        
        $randomClassThreeCoupon = Coupon::find(17);
        $couponProducts         = $randomClassThreeCoupon->couponProducts->first();
        $insertOrder            = $this->withHeaders(self::HEADER_DATA)->post('api/order');
        $order                  = Order::find($insertOrder->json()['id']);
        $addon                  = Addon::find(3);
        $customer               = Customer::find(137);
        $orderGroup             = OrderGroup::create([
            'order_id' => $order['id'], 
            'plan_id' => Plan::find(1)['id'],
        ]);
        $orderGroupId = OrderGroup::where('order_id', $order['id'])->first()['id'];
        $orderGroupAddon = OrderGroupAddon::create([
            'order_group_id' => $orderGroupId,
            'addon_id' => $addon['id']
        ]);
        $order->update([
            'customer_id' => $customer['id']
        ]);
        $addon['order_group_id'] = $orderGroupId;
        $applyCoupon            = $this->withHeaders(self::HEADER_DATA)->post('api/coupon/add-coupon?'.http_build_query(
            [
                'code'      => $randomClassThreeCoupon['code'],
                'order_id'  => $order['id'],
                'hash'      => $customer['hash'],
                'orderGroupsCart' => [
                    'addons' =>[ 
                        'items' => [$addon->toArray()]
                    ]
                ]
            ]
        ));

        return ($applyCoupon->assertJson([
            'total' => $couponProducts['amount']
        ]));
    }
}
