<?php

namespace App\Model;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class CronLog extends Model {

	protected $fillable = [
		'name',
		'status',
		'payload',
		'response'
	];
}