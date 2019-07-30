<?php

namespace App\Model;

use Carbon\Carbon;
use App\Model\Plan;
use App\Model\Addon;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $table = 'order';

    protected $fillable = [
        'active_group_id', 'active_subscription_id', 'order_num', 'status', 'invoice_id', 'hash', 'company_id', 'customer_id', 'date_processed' ,'shipping_fname','shipping_lname', 'shipping_address1', 'shipping_address2', 'shipping_city', 'shipping_state_id', 'shipping_zip'
    ];

    public function isOrder($order)
    {
        if (isset($order->invoice)) {
            if ($order->invoice->type === 2) {
                return true;
            }
        } 
    }

    public function order_group(){
    	return $this->belongsTo('App\Model\OrderGroup')->withTrashed();
    }

    public function bizVerification()
    {
        return $this->hasOne(BusinessVerification::class);
    }

    public function OG()
    {
        return $this->hasOne('App\Model\OrderGroup', 'id', 'active_group_id');
    }

    public function scopeHash($query, $hash)
    {
        return $query->where('hash', $hash);
    }

    public function customer()
    {
        return $this->hasOne('App\Model\Customer', 'id', 'customer_id');
    }
    public function company()
    {
        return $this->hasOne('App\Model\Company', 'id', 'company_id');
    }

    public function invoice()
    {
        return $this->belongsTo('App\Model\Invoice', 'invoice_id' ,'id');
    }

    public function orderCoupon()
    {
        return $this->hasOne('App\Model\OrderCoupon', 'order_id', 'id');
    }

    public function paymentLog()
    {
        return $this->belongsTo(PaymentLog::class);
    }

    public function orderGroup()
    {
        return $this->belongsTo('App\Model\OrderGroup', 'id', 'order_id');
    }

    public function allOrderGroup()
    {
        return $this->hasMany('App\Model\OrderGroup', 'order_id', 'id');
    }

    public function subscriptions()
    {
        return $this->hasMany('App\Model\Subscription', 'order_id', 'id');
    }

    public function standAloneDevices()
    {
        return $this->hasMany('App\Model\CustomerStandaloneDevice', 'order_id', 'id');
    }

    public function standAloneSims()
    {
        return $this->hasMany('App\Model\CustomerStandaloneSim', 'order_id', 'id');
    }

    public function scopecompleteOrders($query)
    {
        return $query->where('status', '1');
    }

    public function getCompareDatesAttribute()
    {
        $today     = Carbon::today();
        $startDate = Carbon::parse($this->customer->billing_start);

        return ($this->customer->billing_start != null && $today->gt($startDate));
    }


    public function planProRate($planId)
    {
        $plan = Plan::find($planId);
        $amount = $plan->amount_recurring;
        return $this->calProRatedAmount($amount);
    }

    public function addonProRate($addonId)
    {
        $addon = Addon::find($addonId);
        $amount = $addon->amount_recurring;
        return $this->calProRatedAmount($amount);
    }

    public function calProRatedAmount($amount)
    {
        $today     = Carbon::today();
        $startDate = Carbon::parse($this->customer->billing_start);
        $endDate   = Carbon::parse($this->customer->billing_end);

        $numberOfDaysLeft  = $endDate->diffInDays($today);
        $totalNumberOfDays = $endDate->diffInDays($startDate);

        return (($numberOfDaysLeft + 1)/($totalNumberOfDays + 1))*$amount;
    }

    public function credits()
    {
        return $this->hasMany('App\Model\Credit', 'order_id', 'id');
    }

    public function getCreatedAtAttribute($value)
    {
        return Carbon::parse($value)->format('M-d-Y h:i A');
    }

    public function getCreatedAtFormatAttribute()
    {
        return Carbon::parse($this->created_at)->format('Y-m-d\Th:i\Z');
    }
}
