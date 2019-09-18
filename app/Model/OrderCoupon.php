<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class OrderCoupon extends Model
{
    protected $table = 'order_coupon';

    protected $fillable = [
        'order_id', 'coupon_id'
    ];

    public function order()
    {
        return $this->belongsTo('App\Model\Order');
    }

    public function orderCouponProduct()
    {
        return $this->hasMany('App\Model\OrderCouponProduct', 'order_coupon_id', 'id');
    }

    public function coupon()
    {
        return $this->belongsTo('App\Model\Coupon');
    }
    

}
