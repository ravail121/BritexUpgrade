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
        'active_group_id', 'active_subscription_id', 'order_num', 'status', 'invoice_id', 'hash', 'company_id', 'customer_id', 'date_processed' 
    ];

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

        return ($numberOfDaysLeft + 1)/($totalNumberOfDays + 1)*$amount;
    }

    public function getCreatedAtAttribute($value)
    {
        return Carbon::parse($value)->format('M-d-Y h:i A');
    }

}
