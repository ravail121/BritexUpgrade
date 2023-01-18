<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

/**
 *
 */
class CustomerProduct extends Model
{

	/**
	 * @var string[]
	 */
	protected $fillable = [
		'customer_id',
		'product_id',
	    'product_type'
	];
}
