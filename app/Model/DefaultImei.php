<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class DefaultImei extends Model
{
    protected $table = 'default_imei';

    protected $fillable = [
        'type',
        'os',
        'code', 
    ];
}
