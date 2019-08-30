<?php

namespace Tests\Feature\Invoice;

use Tests\TestCase;
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
use App\Model\CouponProduct;
use App\Model\CouponMultilinePlanType;

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

        $insertOrder            = $this->withHeaders(self::HEADER_DATA)->post('api/order');
        $order                  = Order::find($insertOrder->json()['id']);
        $testDevice             = Device::inRandomOrder()->limit(1)->first();
        $customer               = Customer::inRandomOrder()->whereNotNull('billing_fname')->limit(1)->first();
        $orderGroup             = OrderGroup::create([
            'order_id' => $order['id'],
            'device_id' => $testDevice['id']
        ]);

        Coupon::create([
            'company_id'    => $customer['company_id'],
            'active'        => 1,
            'class'         => 1,
            'fixed_or_perc' => 1,
            'amount'        => number_format(rand(11,99), 2),
            'code'          => 'coupon_f_test_01',
            'num_cycles'    => 10,
            'max_uses'      => 100,
            'num_uses'      => 0,
            'stackable'     => 1,
            'start_date'    => '2019-07-10 18:09:57	',
            'end_date'      => '2021-07-10 18:09:57	',
            'multiline_min' => 0,
            'multiline_max' => 100,
            'multiline_restrict_plans' => 0
        ]);

        $testClassOneCoupon     = Coupon::where('code', 'coupon_f_test_01')->first();

        $testDevice['order_group_id'] = $orderGroup['id'];
        $applyCoupon            = $this->withHeaders(self::HEADER_DATA)->post('api/coupon/add-coupon?'.http_build_query(
            [
                'code'      => 'coupon_f_test_01',
                'order_id'  => $order['id'],
                'hash'      => $customer['hash'],
                'orderGroupsCart' => [
                    'devices' => [
                        'items' => [ $testDevice ]
                    ]
                ]
            ]
        ));
        return ($applyCoupon->assertJson([
            'total'=>$testClassOneCoupon['amount'],
            'code'  => $testClassOneCoupon['code'],
            'applied_to' => [
                'applied_to_all' => true,
                'applied_to_types' => false,
                'applied_to_products' => false
            ]
        ]));
    }

    public function test_class_2()
    {
        $insertOrder            = $this->withHeaders(self::HEADER_DATA)->post('api/order');
        $order                  = Order::find($insertOrder->json()['id']);
        $testPlan               = Plan::inRandomOrder()->where('type', 1)->limit(1)->first();
        $customer               = Customer::inRandomOrder()->whereNotNull('billing_fname')->limit(1)->first();
        $orderGroup             = OrderGroup::create([
            'order_id' => $order['id'], 
            'plan_id' => $testPlan['id'],
        ]);

        Coupon::create([
            'company_id'    => $customer['company_id'],
            'active'        => 1,
            'class'         => 2,
            'fixed_or_perc' => 1,
            'amount'        => number_format(rand(11,99), 2),
            'code'          => 'coupon_f_test_02',
            'num_cycles'    => 10,
            'max_uses'      => 100,
            'num_uses'      => 0,
            'stackable'     => 1,
            'start_date'    => '2019-07-10 18:09:57	',
            'end_date'      => '2021-07-10 18:09:57	',
            'multiline_min' => 0,
            'multiline_max' => 100,
            'multiline_restrict_plans' => 0
        ]);

        $testClassTwoCoupon     = Coupon::where('code', 'coupon_f_test_02')->first();

        CouponProductType::create([
            'coupon_id' => $testClassTwoCoupon['id'],
            'amount'    => number_format(rand(11,99), 2),
            'type'      => 1,
            'sub_type'  => 0
        ]);

        $couponProductTypes     = $testClassTwoCoupon->couponProductTypes->first();

        $order->update([
            'customer_id' => $customer['id']
        ]);

        $applyCoupon        = $this->withHeaders(self::HEADER_DATA)->post('api/coupon/add-coupon?'.http_build_query(
            [
                'code'      => $testClassTwoCoupon['code'],
                'order_id'  => $order['id'],
                'hash'      => $customer['hash'],
                'orderGroupsCart' => [
                    'plans' =>[ 
                        'items' => [$testPlan->toArray()]
                    ]
                ]
            ]
        ));

        return ($applyCoupon->assertJson([
            'total' => $couponProductTypes['amount'],
            'code'  => $testClassTwoCoupon['code'],
            'applied_to' => [
                'applied_to_all' => false,
                'applied_to_types' => true,
                'applied_to_products' => false
            ]
        ]));
    }


    public function test_class_3()
    {
        $insertOrder            = $this->withHeaders(self::HEADER_DATA)->post('api/order');
        $order                  = Order::find($insertOrder->json()['id']);
        $customer               = Customer::inRandomOrder()->whereNotNull('billing_fname')->limit(1)->first();
        $orderGroup             = OrderGroup::create([
            'order_id' => $order['id'], 
            'plan_id' => Plan::find(1)['id'],
        ]);
        $randomCode = str_random(10);
        Coupon::create([
            'company_id'    => $customer['company_id'],
            'active'        => 1,
            'class'         => 3,
            'fixed_or_perc' => 1,
            'amount'        => number_format(rand(11,99), 2),
            'code'          => $randomCode,
            'num_cycles'    => 10,
            'max_uses'      => 100,
            'num_uses'      => 0,
            'stackable'     => 1,
            'start_date'    => '2019-07-10 18:09:57	',
            'end_date'      => '2021-07-10 18:09:57	',
            'multiline_min' => 0,
            'multiline_max' => 100,
            'multiline_restrict_plans' => 0
        ]);

        $testClassThreeCoupon   = Coupon::where('code', $randomCode)->first();
        $allAddonIds            = Addon::all()->pluck('id')->toArray();
        $randomAddonId          = $allAddonIds[array_rand($allAddonIds)];
        $addon                  = Addon::find($randomAddonId);
     
        CouponProduct::create([
            'coupon_id'     => $testClassThreeCoupon['id'],
            'product_type'  => 4,
            'product_id'    => $randomAddonId,
            'amount'        => number_format(rand(11,99), 2)
        ]);

        $couponProducts         = $testClassThreeCoupon->couponProducts->first();
        $orderGroupId           = OrderGroup::where('order_id', $order['id'])->first()['id'];
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
                'code'      => $testClassThreeCoupon['code'],
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
            'total' => $testClassThreeCoupon->couponProducts->first()['amount']
        ]));
    }

    public function test_class_1_percentage_multiline_restrict()
    {
        $insertOrder            = $this->withHeaders(self::HEADER_DATA)->post('api/order');
        $order                  = Order::find($insertOrder->json()['id']);
        $testPlan               = Plan::inRandomOrder()->where('type', 2)->limit(1)->first();
        $customer               = Customer::inRandomOrder()->whereNotNull('billing_fname')->limit(1)->first();
        $orderGroup             = OrderGroup::create([
            'order_id' => $order['id'],
            'plan_id' => $testPlan['id']
        ]);

        $randomCode = str_random(10);
        Coupon::create([
            'company_id'    => $customer['company_id'],
            'active'        => 1,
            'class'         => 1,
            'fixed_or_perc' => 2,
            'amount'        => number_format(rand(11,99), 2),
            'code'          => $randomCode,
            'num_cycles'    => 10,
            'max_uses'      => 100,
            'num_uses'      => 0,
            'stackable'     => 1,
            'start_date'    => '2019-07-10 18:09:57	',
            'end_date'      => '2021-07-10 18:09:57	',
            'multiline_min' => 0,
            'multiline_max' => 100,
            'multiline_restrict_plans' => 1
        ]);
        $testClassOneCoupon = Coupon::where('code', $randomCode)->first();
        CouponMultilinePlanType::create([
            'coupon_id' => $testClassOneCoupon['id'],
            'plan_type' => $testPlan['type']
        ]);
        $order->update([
            'customer_id' => $customer['id']
        ]);
        
        $applyCoupon        = $this->withHeaders(self::HEADER_DATA)->post('api/coupon/add-coupon?'.http_build_query(
            [
                'code'      => $testClassOneCoupon['code'],
                'order_id'  => $order['id'],
                'hash'      => $customer['hash'],
                'orderGroupsCart' => [
                    'plans' =>[ 
                        'items' => [$testPlan->toArray()]
                    ]
                ]
            ]
        ));
        
        return ($applyCoupon->assertJson([
            'total' => $testClassOneCoupon['amount'] * $testPlan['amount_recurring'] / 100,
            'code'  => $testClassOneCoupon['code'],
            'applied_to' => [
                'applied_to_all' => true,
                'applied_to_types' => false,
                'applied_to_products' => false
            ]
        ]));
    }

}
