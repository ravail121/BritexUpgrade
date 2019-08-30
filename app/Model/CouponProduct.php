<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class CouponProduct extends Model
{
    const PRODUCT_TYPES = [
        'plan'   => 1,
        'device' => 2,
        'sim'    => 3,
        'addon'  => 4
    ];

    protected $fillable = [
        'coupon_id',
        'amount',
        'product_id',
        'product_type'
    ];

    protected $table = 'coupon_product';

    public function coupon()
    {
        return $this->belongsTo('App\Model\Coupon');
    }
}
