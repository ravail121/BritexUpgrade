<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class CouponProductType extends Model
{
    const TYPES = [
        'plan'   => 1,
        'device' => 2,
        'sim'    => 3,
        'addon'  => 4,
    ];

    const SUB_TYPES = [
        'voice'     =>  1,
        'data'      =>  2,
        'wearable'  =>  3,
        'membership'=>  4,
        'digits'    =>  5,
        'cloud'     =>  6,
        'iot'       =>  7
    ];

    protected $table 		= 'coupon_product_type';
    protected $primaryKey	= 'id';

    protected $fillable = [
    	'coupon_id',
    	'amount',
    	'type',
   		'sub_type'
    ];

    public function coupon()
    {
        return $this->belongsTo('App\Model\Coupon');
    }

    public function scopePlanTypes($query)
    {
        return $query->where('type', self::TYPES['plan']);
    }

    public function scopeAddonTypes($query)
    {
        return $query->where('type', self::TYPES['addon']);
    }
}
