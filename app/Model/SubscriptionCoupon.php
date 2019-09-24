<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class SubscriptionCoupon extends Model
{
    protected $table = 'subscription_coupon';

    protected $fillable = [ 'subscription_id', 'coupon_id', 'cycles_remaining'];

  	public function scopeRedeemable($query)
    {
        return $query->where('cycles_remaining', '!=', 0);
    }

    public function coupon()
    {
        return $this->belongsTo('App\Model\Coupon');
    }    

}