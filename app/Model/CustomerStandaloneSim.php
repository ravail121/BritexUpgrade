<?php

namespace App\Model;

use Carbon\Carbon;
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
        'shipping_date',
    ];

    public function sim()
    {
    	return $this->belongsTo('App\Model\Sim', 'sim_id', 'id');
  	}

    public function getShippingDateAttribute($value)
    {
        if (isset($value)) {
            return Carbon::parse($value)->format('M-d-Y');
        }
        return "NA";
    }

    public function scopeShipping($query)
    {
        return $query->where([['status', 'shipping'],['processed', 0 ]]);
    }

    public function order()
    {
        return $this->belongsTo('App\Model\Order');
    }

    public function customer()
    {
        return $this->belongsTo('App\Model\Customer', 'customer_id');
    }   
}
