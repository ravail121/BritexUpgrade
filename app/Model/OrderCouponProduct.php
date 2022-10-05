<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

/**
 * Class OrderCouponProduct
 *
 * @package App\Model
 */
class OrderCouponProduct extends Model
{

	/**
	 * @var string
	 */
	protected $table = 'order_coupon_product';

	/**
	 * @var string[]
	 */
	protected $fillable = [
    	'order_coupon_id',
	    'order_product_type',
	    'order_product_id',
	    'amount'
    ];

	/**
	 * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
	 */
	public function orderCoupon()
    {
        return $this->belongsTo('App\Model\OrderCoupon');
    }
}
