<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

/**
 * Class SubscriptionCoupon
 *
 * @package App\Model
 */
class SubscriptionCoupon extends Model
{
	/**
	 * @var string
	 */
	protected $table = 'subscription_coupon';

	/**
	 * @var string[]
	 */
	protected $fillable = [ 'subscription_id', 'coupon_id', 'cycles_remaining'];

	/**
	 * @param $query
	 *
	 * @return mixed
	 */
	public function scopeRedeemable($query)
    {
        return $query->where('cycles_remaining', '!=', 0);
    }

	/**
	 * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
	 */
	public function coupon()
    {
        return $this->belongsTo('App\Model\Coupon');
    }

}