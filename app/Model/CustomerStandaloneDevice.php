<?php

namespace App\Model;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class CustomerStandaloneDevice
 *
 * @package App\Model
 */
class CustomerStandaloneDevice extends Model
{

	/**
	 * @var string
	 */
	protected $table = 'customer_standalone_device';

	/**
	 * @var string[]
	 */
	protected $fillable = [
    	'customer_id',
    	'device_id',
    	'order_id',
    	'status',
    	'tracking_num',
    	'imei',
        'shipping_date',
        'order_num',
        'processed',
		'subscription_id'
    ];

	/**
	 *
	 */
	const STATUS = [
        'complete'    =>  'complete',
    ];

	/**
	 * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
	 */
	public function device()
    {
    	return $this->belongsTo('App\Model\Device',  'device_id' , 'id');
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
