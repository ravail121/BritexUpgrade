<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Coupon extends Model
{
    protected $table = 'coupon'; 

    protected $fillable = [
        'company_id',
        'active',
        'class',
        'fixed_or_perc',
        'amount',
        'name',
        'code',
        'num_cycles',
        'max_uses',
        'num_uses',
        'stackable',
        'start_date',
        'end_date',
        'multiline_min',
        'multiline_max',
        'multiline_restrict_plans',
    ];
}
