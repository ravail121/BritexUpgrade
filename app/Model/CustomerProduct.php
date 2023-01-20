<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

/**
 *
 */
class CustomerProduct extends Model
{

	/**
	 *
	 */
	const PRODUCT_TYPE = [
		'device'    => 'Device',
		'sim'       => 'Sim',
		'plan'      => 'Plan'
	];


	/**
	 * @var string[]
	 */
	protected $fillable = [
		'customer_id',
		'product_id',
	    'product_type'
	];
}
