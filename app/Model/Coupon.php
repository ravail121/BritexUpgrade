<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Coupon extends Model
{
    const CLASSES = [
        'APPLIES_TO_ALL'              => 1,
        'APPLIES_TO_SPECIFIC_TYPES'   => 2,
        'APPLIES_TO_SPECIFIC_PRODUCT' => 3,
    ];

    const FIXED_PERC_TYPES = [
        'fixed'      => 1,
        'percentage' => 2
    ];

    const TYPES = [
        'subscription_coupon' => 'Subscription coupon',
        'customer_coupon' => 'Customer coupon'
    ];

    protected $table = 'coupon';

    protected $fillable = [
        'company_id',
        'active',
        'class',
        'fixed_or_perc',
        'amount',
        'name',
        'code',
        'num_cycles',
        'max_uses',
        'num_uses',
        'stackable',
        'start_date',
        'end_date',
        'multiline_min',
        'multiline_max',
        'multiline_restrict_plans',
    ];

    const PRODUCT_TYPE = [
        'plan'    => '1',
        'device'  => '2',
        'sim'     => '3',
        'addon'   => '4'
    ];


    public function customerCoupon()
    {
        return $this->hasMany('App\Model\CustomerCoupon');
    }

    public function couponProducts()
    {
        return $this->hasMany('App\Model\CouponProduct');
    }

    public function couponPlanProducts()
    {
        return $this->couponProducts()->planProducts();
    }

    public function couponAddonProducts()
    {
        return $this->couponProducts()->addonProducts();
    }

    public function couponProductTypes()
    {
        return $this->hasMany('App\Model\CouponProductType');
    }

    public function couponProductPlanTypes()
    {
        return $this->couponProductTypes()->planTypes();
    }

    public function couponProductAddonTypes()
    {
        return $this->couponProductTypes()->addonTypes();
    }

    public function multilinePlanTypes()
    {
        return $this->hasMany('App\Model\CouponMultilinePlanType');
    }

}
