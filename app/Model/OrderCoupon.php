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
}
