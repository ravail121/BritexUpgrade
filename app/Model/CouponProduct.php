<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

/**
 * Coupon Product
 */
class CouponProduct extends Model
{

	/**
	 * Product types
	 */
	const PRODUCT_TYPES = [
        'plan'   => 1,
        'device' => 2,
        'sim'    => 3,
        'addon'  => 4
    ];

	/**
	 * @var string[]
	 */
    protected $fillable = [
        'coupon_id',
        'amount',
        'product_id',
        'product_type'
    ];

	/**
	 * @var string
	 */
	protected $table = 'coupon_product';

	/**
	 * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
	 */
	public function coupon()
    {
        return $this->belongsTo('App\Model\Coupon');
    }

	/**
	 * @param $query
	 *
	 * @return mixed
	 */
	public function scopePlanProducts($query)
    {
        return $query->where('product_type', self::PRODUCT_TYPES['plan']);
    }

	/**
	 * @param $query
	 *
	 * @return mixed
	 */
	public function scopeAddonProducts($query)
    {
        return $query->where('product_type', self::PRODUCT_TYPES['addon']);
    }

	/**
	 * @param $query
	 *
	 * @return mixed
	 */
	public function scopeDeviceType($query)
    {
        return $this->where('product_type', 2);
    }

	/**
	 * @return \Illuminate\Database\Eloquent\Relations\HasOne
	 */
	public function device()
    {
        return $this->hasOne('App\Model\Device', 'id', 'product_id');
    }

	/**
	 * @return \Illuminate\Database\Eloquent\Relations\HasOne
	 */
	public function plan()
    {
        return $this->hasOne('App\Model\Plan', 'id', 'product_id');
    }

	/**
	 * @return \Illuminate\Database\Eloquent\Relations\HasOne
	 */
	public function sim()
    {
        return $this->hasOne('App\Model\Sim', 'id', 'product_id');
    }

	/**
	 * @return \Illuminate\Database\Eloquent\Relations\HasOne
	 */
	public function addon()
    {
        return $this->hasOne('App\Model\Addon', 'id', 'product_id');
    }
}
