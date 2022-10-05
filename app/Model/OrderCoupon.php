<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

/**
 * Class OrderCoupon
 *
 * @package App\Model
 */
class OrderCoupon extends Model
{

	/**
	 * @var string
	 */
	protected $table = 'order_coupon';

	/**
	 * @var string[]
	 */
	protected $fillable = [
        'order_id',
		'coupon_id'
    ];

	/**
	 * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
	 */
	public function order()
    {
        return $this->belongsTo('App\Model\Order');
    }

	/**
	 * @return \Illuminate\Database\Eloquent\Relations\HasMany
	 */
	public function orderCouponProduct()
    {
        return $this->hasMany('App\Model\OrderCouponProduct', 'order_coupon_id', 'id');
    }

	/**
	 * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
	 */
	public function coupon()
    {
        return $this->belongsTo('App\Model\Coupon');
    }

	/**
	 *
	 */
	public static function boot() {
        parent::boot();

        static::deleting(function($coupon) {
            $coupon->orderCouponProduct()->delete();
        });
    }
    

}
