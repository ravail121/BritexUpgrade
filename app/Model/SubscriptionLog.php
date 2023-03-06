<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class SubscriptionLog extends Model
{
	/**
	 *
	 */
	const CATEGORY = [
		'replacement-device-ordered'       => 'Replacement Device Ordered',
		'replacement-sim-ordered'          => 'Replacement SIM Ordered',
		'sim-num-changed'                  => 'SIM Num Changed',
		'device-imei-changed'              => 'Device IMEI Changed',
		'number-change-requested'          => 'Number Change Requested',
		'number-change-processed'          => 'Number Change Processed',
	];

	/**
	* @var string
	 */
	protected $table = 'subscription_log';

	/**
	 * @var string[]
	 */
	protected $fillable = [
		'company_id',
		'customer_id',
		'subscription_id',
		'category',
		'product_id',
		'description',
		'old_product',
		'new_product',
		'order_num'
	];


	/**
	 * @return \Illuminate\Database\Eloquent\Relations\HasOne
	 */
	public function subscription(){
		return $this->hasOne('App\Model\Subscription' , 'id', 'subscription_id');
	}

	/**
	 * @return \Illuminate\Database\Eloquent\Relations\HasOne
	 */
	public function customer()
	{
		return $this->hasOne('App\Model\Customer', 'id', 'customer_id');
	}
}
