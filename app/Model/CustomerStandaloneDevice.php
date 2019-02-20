<?php

namespace App\Model;

use Carbon\Carbon;
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
        'shipping_date',
    ];

    public function device()
    {
    	return $this->belongsTo('App\Model\Device',  'device_id' , 'id');
  	}

    public function getShippingDateAttribute($value)
    {
        if (isset($value)) {
            return Carbon::parse($value)->format('M-d-Y');
        }
        return "NA";
    }
}
