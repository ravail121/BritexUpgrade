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
		'customer_id',
		'product_id',
	    'product_type'
	];
}
