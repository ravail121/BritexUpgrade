<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class OrderCouponProduct extends Model
{
    // table new
    protected $table = 'order_coupon_product';

    protected $fillable = ['order_coupon_id', 'order_product_type', 'order_product_id', 'amount'];

    public function orderCoupon()
    {
        return $this->belongsTo('App\Model\OrderCoupon');
    }
}
