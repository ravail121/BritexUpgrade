<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

/**
 * ReplacementProduct model
 */
class ReplacementProduct extends Model
{

	/**
	 * @var string[]
	 */
	protected $fillable = [
		'product_id',
		'product_type',
		'company_id'
	];
}
