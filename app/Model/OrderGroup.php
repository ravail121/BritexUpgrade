<?php

namespace App\Model;

use App\Model\Customer;
use Illuminate\Database\Eloquent\Model;

/**
 * Class OrderGroup
 *
 * @package App\Model
 */
class OrderGroup extends Model
{

	/**
	 * @var string
	 */
	protected $table = 'order_group';

	/**
	 * @var string[]
	 */
	protected $fillable = [
		'order_id',
		'device_id',
		'sim_id',
		'plan_id',
		'plan_prorated_amt',
		'sim_num',
		'sim_type',
		'porting_number',
		'area_code',
		'operating_system',
		'imei_number',
		'subscription_id',
		'old_subscription_plan_id',
		'paid',
		'change_subscription'
	];

	/**
	 * @return int|mixed|null
	 */
	public function getDeviceDetailAttribute()
	{
		if ($this->device_id === 0) {
			$device = 0;
		}elseif ($this->device_id === null) {
			$device = null;
		} else {
			$device = $this->device;
		}

		return $device;
	}

	/**
	 * @return \Illuminate\Database\Eloquent\Relations\HasOne
	 */
	public function order()
	{
		return $this->hasOne('App\Model\Order', 'id', 'order_id');
	}

	/**
	 * @return \Illuminate\Database\Eloquent\Relations\HasOne
	 */
	public function sim()
	{
		return $this->hasOne('App\Model\Sim', 'id', 'sim_id');
	}

	/**
	 * @return \Illuminate\Database\Eloquent\Relations\HasOne
	 */
	public function device()
	{
		return $this->hasOne('App\Model\Device', 'id', 'device_id');
	}

	/**
	 * @return \Illuminate\Database\Eloquent\Relations\HasOne
	 */
	public function plan()
	{
		return $this->hasOne('App\Model\Plan', 'id', 'plan_id');
	}

	/**
	 * @return \Illuminate\Database\Eloquent\Relations\HasMany
	 */
	public function order_group_addon()
	{
		return $this->hasMany('App\Model\OrderGroupAddon', 'order_group_id', 'id');
	}

	/**
	 * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
	 */
	public function subscription()
	{
		return $this->belongsTo('App\Model\Subscription', 'subscription_id');
	}



// ----- Not touching the previously created code as they might be in use ----------

	public function addons()
	{
		return $this->belongsToMany('App\Model\Addon', 'order_group_addon', 'order_group_id', 'addon_id');
	}

// ----- Not touching the previously created code as they might be in use ----------

	public function getCustomerAttribute()
	{
		if ($this->order->customer_id != null) {
			return Customer::find($this->order->customer_id);

		}
		return $this->hasMany('App\Model\OrderGroupAddon');
	}


	// this is a recommended way to declare event handlers
	public static function boot() {
		parent::boot();

		static::deleting(function($ordergroup) { // before delete() method call this
			$ordergroup->order_group_addon()->delete();
			// do the rest of the cleanup...
		});
	}

	public function orderGroupAddon()
	{
		return $this->hasMany('App\Model\OrderGroupAddon', 'order_group_id', 'id');
	}
}
