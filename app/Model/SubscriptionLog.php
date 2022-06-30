<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class SubscriptionLog extends Model
{

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
		'date',
		'category',
		'product_id',
		'description',
		'old_product',
		'new_product'
	];
}
