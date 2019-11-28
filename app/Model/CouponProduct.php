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
    
    public function scopePlanProducts($query)
    {
        return $query->where('product_type', self::PRODUCT_TYPES['plan']);
    }

    public function scopeAddonProducts($query)
    {
        return $query->where('product_type', self::PRODUCT_TYPES['addon']);
    }

    public function scopeDeviceType($query)
    {
        return $this->where('product_type', 2);
    }

    public function device()
    {
        return $this->hasOne('App\Model\Device', 'id', 'product_id');
    }
    public function plan()
    {
        return $this->hasOne('App\Model\Plan', 'id', 'product_id');
    }
    public function sim()
    {
        return $this->hasOne('App\Model\Sim', 'id', 'product_id');
    }
    public function addon()
    {
        return $this->hasOne('App\Model\Addon', 'id', 'product_id');
    }
}
