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
		'device-imei-changed'              => 'Device IMEI Changed'
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
		'new_product'
	];
}
