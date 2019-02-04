<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class CustomerStandaloneSim extends Model
{
    protected $table = 'customer_standalone_sim'; 

    protected $fillable = [
    	'customer_id',
    	'sim_id',
    	'order_id',
    	'status',
    	'tracking_num',
    	'sim_num',
    ];
}
