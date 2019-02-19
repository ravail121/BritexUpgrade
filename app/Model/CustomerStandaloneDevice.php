<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class CustomerStandaloneDevice extends Model
{
    protected $table = 'customer_standalone_device'; 

    protected $fillable = [
    	'customer_id',
    	'device_id',
    	'order_id',
    	'status',
    	'tracking_num',
    	'imei',
    ];

    public function device()
    {
    	return $this->belongsTo('App\Model\Device',  'device_id' , 'id');
  	}
}
