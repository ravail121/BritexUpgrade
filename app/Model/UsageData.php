<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class UsageData extends Model
{

	/**
	 * @var string
	 */
	protected $table = 'usage_data';

	/**
	 * @var string[]
	 */
    protected $fillable = [
		'simnumber',
		'voice',
		'data',
		'sms',
	];
}

