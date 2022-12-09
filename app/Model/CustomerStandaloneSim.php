<?php

namespace App\Model;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class CustomerStandaloneSim extends Model
{

	/**
	 * @var string
	 */
    protected $table = 'customer_standalone_sim';

	/**
	 * @var string[]
	 */
    protected $fillable = [
    	'customer_id',
    	'sim_id',
    	'order_id',
    	'status',
    	'tracking_num',
    	'sim_num',
        'shipping_date',
        'order_num',
        'processed',
	    'closed_date',
	    'subscription_id'
    ];

	/**
	 *
	 */
    const STATUS = [
        'complete'    => 'complete',
	    'closed'      => 'closed'
    ];

	/**
	 * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
	 */
    public function sim()
    {
    	return $this->belongsTo('App\Model\Sim', 'sim_id', 'id');
  	}

	/**
	 * @param $value
	 *
	 * @return string
	 */
    public function getShippingDateAttribute($value)
    {
        if (isset($value)) {
            return Carbon::parse($value)->format('M-d-Y');
        }
        return "NA";
    }

	/**
	 * @param $query
	 *
	 * @return mixed
	 */
    public function scopeShipping($query)
    {
        return $query->where([['status', 'shipping'],['processed', 0 ]]);
    }

	/**
	 * @param $query
	 *
	 * @return mixed
	 */
    public function scopeShippingData($query)
    {
        return $query->where('status', 'shipping');
    }

	/**
	 * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
	 */
    public function order()
    {
        return $this->belongsTo('App\Model\Order');
    }

	/**
	 * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
	 */
    public function customer()
    {
        return $this->belongsTo('App\Model\Customer', 'customer_id');
    }   
}
