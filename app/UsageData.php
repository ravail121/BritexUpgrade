<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UsageData extends Model
{
   
	protected $table = 'usage_data';

    protected $fillable = [
		'simnumber',
		'voice',
		'data',
		'sms',
	];
}

