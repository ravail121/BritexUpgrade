<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class CouponMultilinePlanType extends Model
{
    const PLAN_TYPES = [
        'voice'      => 1,
        'data'       => 1,
        'wearable'   => 1,
        'membership' => 1,
        'digits'     => 1,
        'cloud'      => 1,
    ];

    protected $table = 'coupon_multiline_plan_type';

    protected $fillable = [
        'coupon_id',
        'plan_type'
    ];

    public function coupon()
    {
        return $this->belongsTo('App\Model\Coupon');
    }
}
