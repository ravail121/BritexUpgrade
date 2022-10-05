<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class CouponMultilinePlanType extends Model
{
    const PLAN_TYPES = [
        'voice'      => 1,
        'data'       => 2,
        'wearable'   => 3,
        'membership' => 4,
        'digits'     => 5,
        'cloud'      => 6,
        'Iot'        => 7,
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
