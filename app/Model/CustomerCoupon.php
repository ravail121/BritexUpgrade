<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class CustomerCoupon extends Model
{
    protected $table = 'customer_coupon'; 

    protected $fillable = [
        'customer_id',
        'coupon_id',
        'cycles_remaining', 
    ];
}
