<?php

namespace Tests\Feature\Invoice;

use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Model\Coupon;
use App\Model\CouponProductType;
use App\Model\Order;
use App\Model\Device;
use App\Model\Plan;
use App\Model\Sim;
use App\Model\Addon;
use App\Model\Customer;
use App\Model\OrderGroup;
use App\Model\OrderGroupAddon;
use App\Model\CouponProduct;
use App\Model\CouponMultilinePlanType;
use Carbon\Carbon;

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
        $randomCode = str_random(10);
        Coupon::create([
            'company_id'    => $customer['company_id'],
            'active'        => 1,
            'class'         => 2,
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

        $testClassTwoCoupon     = Coupon::where('code', $randomCode)->first();

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
        $orderGroupAddon        = OrderGroupAddon::create([
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
                'hash'      => $customer['hash']
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

    public function test_class_1_multiple_items()
    {
        $insertOrder            = $this->withHeaders(self::HEADER_DATA)->post('api/order');
        $order                  = Order::find($insertOrder->json()['id']);
        $testPlan               = Plan::inRandomOrder()->where('type', 2)->limit(2)->get();
        $testSim                = Sim::inRandomOrder()->limit(1)->first();
        $testDevice             = Device::inRandomOrder()->where('type', 2)->limit(2)->get();
        $customer               = Customer::inRandomOrder()->whereNotNull('billing_fname')->limit(1)->first();
        $allAddonIds            = Addon::all()->pluck('id')->toArray();
        $randomAddonId          = $allAddonIds[array_rand($allAddonIds)];
   
        $orderGroup1    = OrderGroup::create([
            'order_id'  => $order['id'],
            'plan_id'   => $testPlan->first()['id'],
            'sim_id'    => $testSim['id']
        ]);
        $orderGroup1Addon = OrderGroupAddon::create([
            'order_group_id' => $orderGroup1['id'],
            'addon_id' => $randomAddonId
        ]);
        $orderGroup2    = OrderGroup::create([
            'order_id'  => $order['id'],
            'plan_id'   => $testPlan->last()['id'],
            'sim_id'    => $testSim['id'],
            'device_id' => $testDevice->last()['id']
        ]);
        $orderGroupDevice = OrderGroup::create([
            'order_id'  => $order['id'],
            'device_id' => $testDevice->first()['id']
        ]);
        $randomCode = str_random(10);
        Coupon::create([
            'company_id'    => $customer['company_id'],
            'active'        => 1,
            'class'         => 1,
            'fixed_or_perc' => 1,
            'amount'        => 5,
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
        $testClassOneCoupon = Coupon::where('code', $randomCode)->first();

        $order->update([
            'customer_id' => $customer['id']
        ]);
        
        $applyCoupon        = $this->withHeaders(self::HEADER_DATA)->post('api/coupon/add-coupon?'.http_build_query(
            [
                'code'      => $testClassOneCoupon['code'],
                'order_id'  => $order['id'],
                'hash'      => $customer['hash']
            ]
        ));
        
        return ($applyCoupon->assertJson([
            'total' => $testClassOneCoupon['amount'] *  7, // amount * number of items inserted
            'code'  => $testClassOneCoupon['code'],
            'applied_to' => [
                'applied_to_all' => true,
                'applied_to_types' => false,
                'applied_to_products' => false
            ]
        ]));
    }

    public function test_class_2_multiple_items()
    {
        $insertOrder            = $this->withHeaders(self::HEADER_DATA)->post('api/order');
        $order                  = Order::find($insertOrder->json()['id']);
        $testPlan               = Plan::inRandomOrder()->where('type', 2)->limit(2)->get();
        $testSim                = Sim::inRandomOrder()->limit(1)->first();
        $testDevice             = Device::inRandomOrder()->where('type', 2)->limit(2)->get();
        $customer               = Customer::inRandomOrder()->whereNotNull('billing_fname')->limit(1)->first();
        $allAddonIds            = Addon::all()->pluck('id')->toArray();
        $randomAddonId          = $allAddonIds[array_rand($allAddonIds)];
        $randomAddon            = Addon::find($randomAddonId);
        $amountForDevice        = rand(11,99);
        $amountForAddon         = rand(1,9);
        $orderGroup1    = OrderGroup::create([
            'order_id'  => $order['id'],
            'plan_id'   => $testPlan->first()['id'],
            'sim_id'    => $testSim['id']
        ]);
        $orderGroup1Addon = OrderGroupAddon::create([
            'order_group_id' => $orderGroup1['id'],
            'addon_id' => $randomAddonId
        ]);
        $orderGroup2    = OrderGroup::create([
            'order_id'  => $order['id'],
            'plan_id'   => $testPlan->last()['id'],
            'sim_id'    => $testSim['id'],
            'device_id' => $testDevice->last()['id']
        ]);
        $orderGroupDevice = OrderGroup::create([
            'order_id'  => $order['id'],
            'device_id' => $testDevice->first()['id']
        ]);
        $randomCode = str_random(10);
        Coupon::create([
            'company_id'    => $customer['company_id'],
            'active'        => 1,
            'class'         => 2,
            'fixed_or_perc' => 1,
            'amount'        => 5,
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
        $couponUsed = Coupon::where('code', $randomCode)->first();
        $forDevice = CouponProductType::create([
            'coupon_id' => $couponUsed['id'],
            'amount'    => $amountForDevice,
            'type'      => 2,
            'sub_type'  => 0
        ]);
        $forAddons = CouponProductType::create([
            'coupon_id' => $couponUsed['id'],
            'amount'    => $amountForAddon,
            'type'      => 4,
            'sub_type'  => 0
        ]);
        $order->update([
            'customer_id' => $customer['id']
        ]);
        $testDevice->first()['order_group_id'] = $orderGroupDevice['id'];
        $testDevice->last()['order_group_id']  = $orderGroup2['id'];
        $randomAddon['order_group_id'] = $orderGroup1['id'];
        $applyCoupon        = $this->withHeaders(self::HEADER_DATA)->post('api/coupon/add-coupon?'.http_build_query(
            [
                'code'      => $couponUsed['code'],
                'order_id'  => $order['id'],
                'hash'      => $customer['hash'],
                'orderGroupsCart' => [
                    'devices' =>[ 
                        'items' => [$testDevice->first()->toArray(), $testDevice->last()->toArray()]
                    ],
                    'plans' => [
                        'items' => [$testPlan->first()->toArray(), $testPlan->last()->toArray()]
                    ],
                    'sims'  => [
                        'items' => [$testSim->toArray()]
                    ],
                    'addons' => [
                        'items' => [$randomAddon->toArray()]
                    ]
                ]
            ]
        ));
        
        return ($applyCoupon->assertJson([
            'total' => ($forDevice['amount'] * 2) + $forAddons['amount'], // only for devices and addons
            'code'  => $couponUsed['code'],
            'applied_to' => [
                'applied_to_all' => false,
                'applied_to_types' => true,
                'applied_to_products' => false
            ]
        ]));
    }

    public function test_class_3_multiple_items()
    {
        $insertOrder            = $this->withHeaders(self::HEADER_DATA)->post('api/order');
        $order                  = Order::find($insertOrder->json()['id']);
        $testPlan               = Plan::inRandomOrder()->where('type', 1)->limit(2)->get();
        $testSim                = Sim::inRandomOrder()->limit(2)->get();
        $testDevice             = Device::inRandomOrder()->where('type', 2)->limit(2)->get();
        $customer               = Customer::inRandomOrder()->whereNotNull('billing_fname')->limit(1)->first();
        $allAddonIds            = Addon::all()->pluck('id')->toArray();
        $randomAddonId          = $allAddonIds[array_rand($allAddonIds)];
        $randomAddon            = Addon::find($randomAddonId);
        $amountForSim           = rand(11,99);
        $amountForPlan          = rand(1,9);
        $orderGroup1    = OrderGroup::create([
            'order_id'  => $order['id'],
            'plan_id'   => $testPlan->first()['id'],
            'sim_id'    => $testSim->last()['id']
        ]);
        $orderGroup1Addon = OrderGroupAddon::create([
            'order_group_id' => $orderGroup1['id'],
            'addon_id' => $randomAddonId
        ]);
        $orderGroup2    = OrderGroup::create([
            'order_id'  => $order['id'],
            'plan_id'   => $testPlan->last()['id'],
            'sim_id'    => $testSim->first()['id'],
            'device_id' => $testDevice->last()['id']
        ]);
        $orderGroupDevice = OrderGroup::create([
            'order_id'  => $order['id'],
            'device_id' => $testDevice->first()['id']
        ]);
        $randomCode = str_random(10);

        Coupon::create([
            'company_id'    => $customer['company_id'],
            'active'        => 1,
            'class'         => 3,
            'fixed_or_perc' => 1,
            'amount'        => 5,
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
        $couponUsed = Coupon::where('code', $randomCode)->first();
        $forSim = CouponProduct::create([
            'coupon_id' => $couponUsed['id'],
            'product_type' => 3,
            'product_id'   => $testSim->last()['id'],
            'amount'    => $amountForSim,
        ]);
        $forPlan = CouponProduct::create([
            'coupon_id' => $couponUsed['id'],
            'product_type' => 1,
            'product_id'   => $testPlan->first()['id'],
            'amount'    => $amountForPlan,
        ]);
        $order->update([
            'customer_id' => $customer['id']
        ]);
        $testPlan->first()['order_group_id'] = $orderGroupDevice['id'];
        $testSim->last()['order_group_id']  = $orderGroup2['id'];

        $applyCoupon        = $this->withHeaders(self::HEADER_DATA)->post('api/coupon/add-coupon?'.http_build_query(
            [
                'code'      => $couponUsed['code'],
                'order_id'  => $order['id'],
                'hash'      => $customer['hash'],
                'orderGroupsCart' => [
                    'devices' =>[ 
                        'items' => [$testDevice->first()->toArray(), $testDevice->last()->toArray()]
                    ],
                    'plans' => [
                        'items' => [$testPlan->first()->toArray(), $testPlan->last()->toArray()]
                    ],
                    'sims'  => [
                        'items' => [$testSim->last()->toArray()]
                    ],
                    'addons' => [
                        'items' => [$randomAddon->toArray()]
                    ]
                ]
            ]
        ));
        
        return ($applyCoupon->assertJson([
            'total' => $forSim['amount'] + $forPlan['amount'], // only for particular plan and sim
            'code'  => $couponUsed['code'],
            'applied_to' => [
                'applied_to_all' => false,
                'applied_to_types' => false,
                'applied_to_products' => true
            ]
        ]));
    }

    public function test_not_eligible_multiline_min()
    {
        //Test for customer without minimum subscriptions required.
        $insertOrder            = $this->withHeaders(self::HEADER_DATA)->post('api/order');
        $order                  = Order::find($insertOrder->json()['id']);
        $testDevice             = Device::inRandomOrder()->limit(1)->first();
        $customers              = Customer::inRandomOrder()->whereNotNull('billing_fname')->get();
        $orderGroup             = OrderGroup::create([
            'order_id' => $order['id'],
            'device_id' => $testDevice['id']
        ]);
        foreach ($customers as $customer) {
            if (!count($customer->billableSubscriptions)) {

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
                    'multiline_min' => 1,
                    'multiline_max' => 2,
                    'multiline_restrict_plans' => 0
                ]);
                $testMultilineMin   = Coupon::where('code', $randomCode)->first();
                $applyCoupon        = $this->withHeaders(self::HEADER_DATA)->post('api/coupon/add-coupon?'.http_build_query(
                    [
                        'code'      => $testMultilineMin['code'],
                        'order_id'  => $order['id'],
                        'hash'      => $customer['hash'],
                        'orderGroupsCart' => [
                            'devices' =>[ 
                                'items' => [$testDevice->toArray()]
                            ]
                        ]
                    ]
                ));
                return ($applyCoupon->assertJson([
                    'error' => 'Minimum subscription requirements not met'
                ]));
            }
        }

    }

    public function test_not_eligible_multiline_max()
    {
        //Test for customer without maximum subscriptions required.
        $insertOrder            = $this->withHeaders(self::HEADER_DATA)->post('api/order');
        $order                  = Order::find($insertOrder->json()['id']);
        $testDevice             = Device::inRandomOrder()->limit(1)->first();
        $customers              = Customer::inRandomOrder()->whereNotNull('billing_fname')->get();
        $orderGroup             = OrderGroup::create([
            'order_id' => $order['id'],
            'device_id' => $testDevice['id']
        ]);
        foreach ($customers as $customer) {
            if (count($customer->billableSubscriptions) > 2) {

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
                    'multiline_min' => 1,
                    'multiline_max' => 2,
                    'multiline_restrict_plans' => 0
                ]);
                $testMultilineMax   = Coupon::where('code', $randomCode)->first();
                $applyCoupon        = $this->withHeaders(self::HEADER_DATA)->post('api/coupon/add-coupon?'.http_build_query(
                    [
                        'code'      => $testMultilineMax['code'],
                        'order_id'  => $order['id'],
                        'hash'      => $customer['hash'],
                        'orderGroupsCart' => [
                            'devices' =>[ 
                                'items' => [$testDevice->toArray()]
                            ]
                        ]
                    ]
                ));
                return ($applyCoupon->assertJson([
                    'error' => 'Maximum subscription requirements not met'
                ]));
            }
        }

    }

    public function test_not_active_coupon()
    {
        $randomCode = str_random(10);
        Coupon::create([
            'company_id'    => 1,
            'active'        => 0,
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
            'multiline_min' => 1,
            'multiline_max' => 2,
            'multiline_restrict_plans' => 0
        ]);
        $applyCoupon        = $this->withHeaders(self::HEADER_DATA)->post('api/coupon/add-coupon?'.http_build_query(
            [
                'code'      => $randomCode,
                'order_id'  => 1,
                'hash'      => $randomCode,
                'orderGroupsCart' => []
            ]
        ));
        return ($applyCoupon->assertJson([
            'error' => 'Coupon is not active'
        ]));

    }

    public function test_wrong_company_id_coupon()
    {
        $insertOrder            = $this->withHeaders(self::HEADER_DATA)->post('api/order');
        $order                  = Order::find($insertOrder->json()['id']);
        $testDevice             = Device::inRandomOrder()->limit(1)->first();
        $customer               = Customer::inRandomOrder()->whereNotNull('billing_fname')->first();
        $orderGroup             = OrderGroup::create([
            'order_id' => $order['id'],
            'device_id' => $testDevice['id']
        ]);

        $randomCode = str_random(10);
        Coupon::create([
            'company_id'    => $customer['company_id'] + 3,
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
            'multiline_min' => 1,
            'multiline_max' => 2,
            'multiline_restrict_plans' => 0
        ]);
        $testWrongCompany   = Coupon::where('code', $randomCode)->first();
        $applyCoupon        = $this->withHeaders(self::HEADER_DATA)->post('api/coupon/add-coupon?'.http_build_query(
            [
                'code'      => $testWrongCompany['code'],
                'order_id'  => $order['id'],
                'hash'      => $customer['hash'],
                'orderGroupsCart' => [
                    'devices' =>[ 
                        'items' => [$testDevice->toArray()]
                    ]
                ]
            ]
        ));
        return ($applyCoupon->assertJson([
            'error' => 'Coupon is Invalid'
        ]));

    }

    public function test_expired_coupon()
    {
        $insertOrder            = $this->withHeaders(self::HEADER_DATA)->post('api/order');
        $order                  = Order::find($insertOrder->json()['id']);
        $testDevice             = Device::inRandomOrder()->limit(1)->first();
        $customer               = Customer::inRandomOrder()->whereNotNull('billing_fname')->first();
        $orderGroup             = OrderGroup::create([
            'order_id' => $order['id'],
            'device_id' => $testDevice['id']
        ]);

        $randomCode = str_random(10);
        $startDate  = Carbon::now()->subDays(370);
        $endDate    = Carbon::now()->subDays(5);
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
            'start_date'    => $startDate,
            'end_date'      => $endDate,
            'multiline_min' => 0,
            'multiline_max' => 0,
            'multiline_restrict_plans' => 0
        ]);
        $testExpiredCoupon  = Coupon::where('code', $randomCode)->first();
        $applyCoupon        = $this->withHeaders(self::HEADER_DATA)->post('api/coupon/add-coupon?'.http_build_query(
            [
                'code'      => $testExpiredCoupon['code'],
                'order_id'  => $order['id'],
                'hash'      => $customer['hash'],
                'orderGroupsCart' => [
                    'devices' =>[ 
                        'items' => [$testDevice->toArray()]
                    ]
                ]
            ]
        ));
        return ($applyCoupon->assertJson([
            'error' => 'Coupon expired on '.$endDate
        ]));

    }

    public function test_coupon_used_prematurely()
    {
        $insertOrder            = $this->withHeaders(self::HEADER_DATA)->post('api/order');
        $order                  = Order::find($insertOrder->json()['id']);
        $testDevice             = Device::inRandomOrder()->limit(1)->first();
        $customer               = Customer::inRandomOrder()->whereNotNull('billing_fname')->first();
        $orderGroup             = OrderGroup::create([
            'order_id' => $order['id'],
            'device_id' => $testDevice['id']
        ]);

        $randomCode = str_random(10);
        $startDate  = Carbon::now()->addDays(5);
        $endDate    = Carbon::now()->addDays(370);
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
            'start_date'    => $startDate,
            'end_date'      => $endDate,
            'multiline_min' => 0,
            'multiline_max' => 0,
            'multiline_restrict_plans' => 0
        ]);
        $couponUsed         = Coupon::where('code', $randomCode)->first();
        $applyCoupon        = $this->withHeaders(self::HEADER_DATA)->post('api/coupon/add-coupon?'.http_build_query(
            [
                'code'      => $couponUsed['code'],
                'order_id'  => $order['id'],
                'hash'      => $customer['hash'],
                'orderGroupsCart' => [
                    'devices' =>[ 
                        'items' => [$testDevice->toArray()]
                    ]
                ]
            ]
        ));
        return ($applyCoupon->assertJson([
            'error' => 'Coupon is valid from '.$startDate
        ]));

    }

    public function test_coupon_max_used()
    {
        $insertOrder            = $this->withHeaders(self::HEADER_DATA)->post('api/order');
        $order                  = Order::find($insertOrder->json()['id']);
        $testDevice             = Device::inRandomOrder()->limit(1)->first();
        $customer               = Customer::inRandomOrder()->whereNotNull('billing_fname')->first();
        $orderGroup             = OrderGroup::create([
            'order_id' => $order['id'],
            'device_id' => $testDevice['id']
        ]);

        $randomCode = str_random(10);
        $startDate  = Carbon::now();
        $endDate    = Carbon::now()->addDays(370);
        Coupon::create([
            'company_id'    => $customer['company_id'],
            'active'        => 1,
            'class'         => 1,
            'fixed_or_perc' => 2,
            'amount'        => number_format(rand(11,99), 2),
            'code'          => $randomCode,
            'num_cycles'    => 10,
            'max_uses'      => 100,
            'num_uses'      => 100,
            'stackable'     => 1,
            'start_date'    => $startDate,
            'end_date'      => $endDate,
            'multiline_min' => 0,
            'multiline_max' => 0,
            'multiline_restrict_plans' => 0
        ]);
        $couponUsed         = Coupon::where('code', $randomCode)->first();
        $applyCoupon        = $this->withHeaders(self::HEADER_DATA)->post('api/coupon/add-coupon?'.http_build_query(
            [
                'code'      => $couponUsed['code'],
                'order_id'  => $order['id'],
                'hash'      => $customer['hash'],
                'orderGroupsCart' => [
                    'devices' =>[ 
                        'items' => [$testDevice->toArray()]
                    ]
                ]
            ]
        ));
        return ($applyCoupon->assertJson([
            'error' => 'Coupon has reached maximum usage'
        ]));

    }

    public function test_coupon_missing_multiline_data()
    {
        $insertOrder            = $this->withHeaders(self::HEADER_DATA)->post('api/order');
        $order                  = Order::find($insertOrder->json()['id']);
        $testDevice             = Device::inRandomOrder()->limit(1)->first();
        $customer               = Customer::inRandomOrder()->whereNotNull('billing_fname')->first();
        $orderGroup             = OrderGroup::create([
            'order_id' => $order['id'],
            'device_id' => $testDevice['id']
        ]);

        $randomCode = str_random(10);
        $startDate  = Carbon::now();
        $endDate    = Carbon::now()->addDays(370);
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
            'start_date'    => $startDate,
            'end_date'      => $endDate,
            'multiline_min' => 0,
            'multiline_max' => 0,
            'multiline_restrict_plans' => 1
        ]);
        $couponUsed         = Coupon::where('code', $randomCode)->first();
        $applyCoupon        = $this->withHeaders(self::HEADER_DATA)->post('api/coupon/add-coupon?'.http_build_query(
            [
                'code'      => $couponUsed['code'],
                'order_id'  => $order['id'],
                'hash'      => $customer['hash'],
                'orderGroupsCart' => [
                    'devices' =>[ 
                        'items' => [$testDevice->toArray()]
                    ]
                ]
            ]
        ));
        return ($applyCoupon->assertJson([
            'error' => 'Multiline plan data missing'
        ]));

    }

    public function test_coupon_types_missing_data()
    {
        $insertOrder            = $this->withHeaders(self::HEADER_DATA)->post('api/order');
        $order                  = Order::find($insertOrder->json()['id']);
        $testDevice             = Device::inRandomOrder()->limit(1)->first();
        $customer               = Customer::inRandomOrder()->whereNotNull('billing_fname')->first();
        $orderGroup             = OrderGroup::create([
            'order_id' => $order['id'],
            'device_id' => $testDevice['id']
        ]);

        $randomCode = str_random(10);
        $startDate  = Carbon::now();
        $endDate    = Carbon::now()->addDays(370);
        Coupon::create([
            'company_id'    => $customer['company_id'],
            'active'        => 1,
            'class'         => 2,
            'fixed_or_perc' => 2,
            'amount'        => number_format(rand(11,99), 2),
            'code'          => $randomCode,
            'num_cycles'    => 10,
            'max_uses'      => 100,
            'num_uses'      => 0,
            'stackable'     => 1,
            'start_date'    => $startDate,
            'end_date'      => $endDate,
            'multiline_min' => 0,
            'multiline_max' => 0,
            'multiline_restrict_plans' => 0
        ]);
        $couponUsed         = Coupon::where('code', $randomCode)->first();
        $applyCoupon        = $this->withHeaders(self::HEADER_DATA)->post('api/coupon/add-coupon?'.http_build_query(
            [
                'code'      => $couponUsed['code'],
                'order_id'  => $order['id'],
                'hash'      => $customer['hash'],
                'orderGroupsCart' => [
                    'devices' =>[ 
                        'items' => [$testDevice->toArray()]
                    ]
                ]
            ]
        ));
        return ($applyCoupon->assertJson([
            'error' => 'Coupon product types data missing'
        ]));

    }

    public function test_coupon_prodcuts_missing_data()
    {
        $insertOrder            = $this->withHeaders(self::HEADER_DATA)->post('api/order');
        $order                  = Order::find($insertOrder->json()['id']);
        $testDevice             = Device::inRandomOrder()->limit(1)->first();
        $customer               = Customer::inRandomOrder()->whereNotNull('billing_fname')->first();
        $orderGroup             = OrderGroup::create([
            'order_id' => $order['id'],
            'device_id' => $testDevice['id']
        ]);

        $randomCode = str_random(10);
        $startDate  = Carbon::now();
        $endDate    = Carbon::now()->addDays(370);
        Coupon::create([
            'company_id'    => $customer['company_id'],
            'active'        => 1,
            'class'         => 3,
            'fixed_or_perc' => 2,
            'amount'        => number_format(rand(11,99), 2),
            'code'          => $randomCode,
            'num_cycles'    => 10,
            'max_uses'      => 100,
            'num_uses'      => 0,
            'stackable'     => 1,
            'start_date'    => $startDate,
            'end_date'      => $endDate,
            'multiline_min' => 0,
            'multiline_max' => 0,
            'multiline_restrict_plans' => 0
        ]);
        $couponUsed         = Coupon::where('code', $randomCode)->first();
        $applyCoupon        = $this->withHeaders(self::HEADER_DATA)->post('api/coupon/add-coupon?'.http_build_query(
            [
                'code'      => $couponUsed['code'],
                'order_id'  => $order['id'],
                'hash'      => $customer['hash'],
                'orderGroupsCart' => [
                    'devices' =>[ 
                        'items' => [$testDevice->toArray()]
                    ]
                ]
            ]
        ));
        return ($applyCoupon->assertJson([
            'error' => 'Coupon products data missing'
        ]));

    }

    public function test_wrong_code()
    {
        $insertOrder            = $this->withHeaders(self::HEADER_DATA)->post('api/order');
        $order                  = Order::find($insertOrder->json()['id']);
        $testDevice             = Device::inRandomOrder()->limit(1)->first();
        $customer               = Customer::inRandomOrder()->whereNotNull('billing_fname')->first();
        $orderGroup             = OrderGroup::create([
            'order_id' => $order['id'],
            'device_id' => $testDevice['id']
        ]);

        $randomCode = str_random(10);

        $applyCoupon        = $this->withHeaders(self::HEADER_DATA)->post('api/coupon/add-coupon?'.http_build_query(
            [
                'code'      => $randomCode,
                'order_id'  => $order['id'],
                'hash'      => $customer['hash'],
                'orderGroupsCart' => [
                    'devices' =>[ 
                        'items' => [$testDevice->toArray()]
                    ]
                ]
            ]
        ));

        return ($applyCoupon->assertJson([
            'error' => 'Invalid coupon code'
        ]));

    }

}
