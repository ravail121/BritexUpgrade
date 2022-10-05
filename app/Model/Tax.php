<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Tax extends Model
{
    protected $table = 'tax'; 

    protected $fillable = [
        'company_id', 
        'state',
        'rate',
    ];
}
