<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UsageData extends Model
{
   

    protected $fillable = [
		'simnumber',
		'voice',
		'data',
		'sms',
	];
}

