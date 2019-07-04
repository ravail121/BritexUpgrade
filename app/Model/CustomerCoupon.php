<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class CustomerCoupon extends Model
{
    protected $table = 'customer_coupon';
    protected $primaryKey = 'id';

    protected $fillable = [
        'customer_id',
        'coupon_id',
        'cycles_remaining', 
    ];

    /**
     * Customer coupons that can be redeemed
     * @param  [type] $query
     * @return $query
     */
    public function scopeRedeemable($query)
    {
        return $query->where('cycles_remaining', '!=', 0);
    }

    public function coupon()
    {
    	return $this->belongsTo('App\Model\Coupon');
    }

}
