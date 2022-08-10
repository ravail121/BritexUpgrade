<?php

namespace App\Model;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Order
 *
 * @package App\Model
 */
class Order extends Model
{

	/**
	 * @var string
	 */
	protected $table = 'order';

	/**
	 * @var string[]
	 */
	protected $fillable = [
        'active_group_id',
		'active_subscription_id',
		'order_num',
		'status',
		'invoice_id',
		'hash',
		'company_id',
		'customer_id',
		'date_processed',
		'shipping_fname',
		'shipping_lname',
		'shipping_address1',
		'shipping_address2',
		'shipping_city',
		'shipping_state_id',
		'shipping_zip'
    ];

	/**
	 * @param $order
	 *
	 * @return bool
	 */
	public function isOrder($order)
    {
        if (isset($order->invoice)) {
            if ($order->invoice->type === 2) {
                return true;
            }
        } 
    }

	/**
	 * @return mixed
	 */
	public function order_group(){
    	return $this->belongsTo('App\Model\OrderGroup')->withTrashed();
    }

	/**
	 * @return \Illuminate\Database\Eloquent\Relations\HasOne
	 */
	public function bizVerification()
    {
        return $this->hasOne(BusinessVerification::class);
    }

	/**
	 * @return \Illuminate\Database\Eloquent\Relations\HasOne
	 */
	public function OG()
    {
        return $this->hasOne('App\Model\OrderGroup', 'id', 'active_group_id');
    }

	/**
	 * @param $query
	 * @param $hash
	 *
	 * @return mixed
	 */
	public function scopeHash($query, $hash)
    {
        return $query->where('hash', $hash);
    }

	/**
	 * @return \Illuminate\Database\Eloquent\Relations\HasOne
	 */
	public function customer()
    {
        return $this->hasOne('App\Model\Customer', 'id', 'customer_id');
    }

	/**
	 * @return \Illuminate\Database\Eloquent\Relations\HasOne
	 */
	public function company()
    {
        return $this->hasOne('App\Model\Company', 'id', 'company_id');
    }

	/**
	 * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
	 */
	public function invoice()
    {
        return $this->belongsTo('App\Model\Invoice', 'invoice_id' ,'id');
    }


	/**
	 * @return \Illuminate\Database\Eloquent\Relations\HasMany
	 */
	public function orderCoupon()
    {
        return $this->hasMany('App\Model\OrderCoupon', 'order_id', 'id');
    }

	/**
	 * @return \Illuminate\Database\Eloquent\Relations\HasOne
	 */
	public function payLog()
    {
        return $this->hasOne('App\Model\PaymentLog', 'order_id', 'id');
    }

	/**
	 * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
	 */
	public function paymentLog()
    {
        return $this->belongsTo(PaymentLog::class);
    }

	/**
	 * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
	 */
	public function orderGroup()
    {
        return $this->belongsTo('App\Model\OrderGroup', 'id', 'order_id');
    }

	/**
	 * @return \Illuminate\Database\Eloquent\Relations\HasMany
	 */
	public function allOrderGroup()
    {
        return $this->hasMany('App\Model\OrderGroup', 'order_id', 'id');
    }

	/**
	 * @return \Illuminate\Database\Eloquent\Relations\HasMany
	 */
	public function subscriptions()
    {
        return $this->hasMany('App\Model\Subscription', 'order_id', 'id');
    }

	/**
	 * @return \Illuminate\Database\Eloquent\Relations\HasMany
	 */
	public function standAloneDevices()
    {
        return $this->hasMany('App\Model\CustomerStandaloneDevice', 'order_id', 'id');
    }

	/**
	 * @return \Illuminate\Database\Eloquent\Relations\HasMany
	 */
	public function standAloneSims()
    {
        return $this->hasMany('App\Model\CustomerStandaloneSim', 'order_id', 'id');
    }

	/**
	 * @param $query
	 *
	 * @return mixed
	 */
	public function scopecompleteOrders($query)
    {
        return $query->where('status', '1');
    }

	/**
	 * @return bool
	 */
	public function getCompareDatesAttribute()
    {
        $today     = Carbon::today();
        $startDate = Carbon::parse($this->customer->billing_start);

        return ($this->customer->billing_start != null && $today->gt($startDate));
    }


	/**
	 * @param $planId
	 *
	 * @return float|int
	 */
	public function planProRate($planId)
    {
        $plan = Plan::find($planId);
        $amount = $plan->amount_recurring;
        return $this->calProRatedAmount($amount);
    }

	/**
	 * @param $addonId
	 *
	 * @return float|int
	 */
	public function addonProRate($addonId)
    {
        $addon = Addon::find($addonId);
        $amount = $addon->amount_recurring;
        return $this->calProRatedAmount($amount);
    }

	/**
	 * @param $amount
	 *
	 * @return float|int
	 */
	public function calProRatedAmount($amount)
    {
        $today     = Carbon::today();
        $startDate = Carbon::parse($this->customer->billing_start);
        $endDate   = Carbon::parse($this->customer->billing_end);

        $numberOfDaysLeft  = $endDate->diffInDays($today);
        $totalNumberOfDays = $endDate->diffInDays($startDate);

        return (($numberOfDaysLeft + 1)/($totalNumberOfDays + 1))*$amount;
    }

	/**
	 * @return \Illuminate\Database\Eloquent\Relations\HasMany
	 */
	public function credits()
    {
        return $this->hasMany('App\Model\Credit', 'order_id', 'id');
    }

	/**
	 * @param $value
	 *
	 * @return string
	 */
	public function getCreatedAtAttribute($value)
    {
        return Carbon::parse($value)->format('M-d-Y h:i A');
    }

	/**
	 * @return string
	 */
	public function getCreatedAtFormatAttribute()
    {
        return Carbon::parse($this->created_at)->format('Y-m-d\Th:i\Z');
    }


	/**
	 * @return string
	 */
	public function getUpdatedAtFormatAttribute()
	{
		return Carbon::parse($this->updated_at)->format('Y-m-d\Th:i\Z');
	}

	/**
	 * @param $order
	 *
	 * @return mixed
	 */
	public static function oldCredits($order)
    {
        return $order->invoice->creditsToInvoice->where('credit_id', '!=', $order->credits->first()->id)->sum('amount');
    }

	/**
	 * @param $date
	 *
	 * @return string
	 */
	public static function formatDate($date)
    {
        return Carbon::parse($date)->format('m/d/Y');
    }

	/**
	 * @param $number
	 *
	 * @return string|string[]|null
	 */
	public static function phoneNumberFormatted($number)
    {
        $number = preg_replace("/[^\d]/","",$number);
    
        $length = strlen($number);

        if($length == 10) {
            $number = preg_replace("/^1?(\d{3})(\d{3})(\d{4})$/", "$1-$2-$3", $number);
        }
            
        return $number;
    }

	/**
	 * @param $query
	 *
	 * @return mixed
	 */
	public function scopePendingOrders($query)
	{
		return $query->where('status', 0);
	}
}
