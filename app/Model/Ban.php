<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Ban extends Model
{
    protected $table = 'ban';

    protected $fillable = [
        'name', 'number', 'billing_start_day', 'fan_id', 'node_id', 'voice_limit', 'data_limit', 'total_limit', 'company_id',
    ];
}
