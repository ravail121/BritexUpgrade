<?php

namespace App\Model;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Customer extends Authenticatable
{

    use Notifiable;
    
    protected $table = 'customer'; 
    protected $fillable = [
        'hash',  
        'company_id',
        'business_verification_id',
        'business_verified',
        'fname',
        'lname',
        'password',
        'phone',
        'alternate_phone',
        'pin',
        'email',
        'company_name',
        'subscription_start_date',
        'billing_start',
        'billing_end',
        'primary_payment_method',
        'primary_payment_card',
        'account_suspended',
        'billing_address1',
        'billing_address2', 
        'billing_city',
        'billing_state_id',
        'billing_zip',
        'shipping_address1',
        'shipping_address2',
        'shipping_city',
        'shipping_state_id',
        'shipping_zip',
    ];

    protected $attributes = [
        'auto_pay' => 0,
    ];

    public function company()
    {
    	return $this->hasOne('App\Model\Company', 'id');

    }

    public function subscription()
    {
     return $this->hasMany('App\Model\Subscription');
    }

    public function pending_charge()
    {
     return $this->hasMany('App\Model\PendingCharge');
    }

    public function invoice()
    {
     return $this->hasMany('App\Model\Invoice');
    }

    public function orders()
    {
        return $this->hasMany('App\Model\Order');
    }

    public function order()
    {
        return $this->belongsTo('App\Model\Order');
    }

     public function scopeHash($query, $hash)
    {
        return $query->where('hash', $hash)->first();
    }

     // public function pending_charge(){
     // 	return $this->belongsTo('App\Model\PendingCharge' , 'customer_id');
     // }
    public function tax()
    {
     	return $this->belongsTo('App\Model\Tax' , 'company_id');
     }
    public function coupon()
    {
        return $this->hasOne('App\Model\coupon', 'id');
    }

    public function BizVerification()
    {
        return $this->belongsTo('App\Model\BusinessVerification');
    }

    public function customerCreditCards()
    {
        return $this->hasMany('App\Model\CustomerCreditCard');
    }

    public function creditAmount()
    {
        return $this->hasMany('App\Model\Credit','customer_id');
    }

    public function credit()
    {
        return $this->hasMany('App\Model\Credit');
    }


    public function getFullNameAttribute()
    {
        return $this->fname.' '.$this->lname;
    }

    public function getZipAddressAttribute()
    {
        return $this->shipping_city.', '.$this->shipping_state_id.' '.$this->shipping_zip;
    }


    public function getFiveDaysBeforeAttribute()
    {
        $today          = self::currentDate();
        $endDate        = $this->parseEndDate();
        $fiveDaysBefore = $endDate->subDays(5);

        return ($today->gte($fiveDaysBefore) && $today->lte($endDate));

    }


    public function getTodayGreaterThanBillingEndAttribute()
    {
        $today    = self::currentDate();
        $endDate  = $this->parseEndDate();
        return $today->gt($endDate);

    }

    public function getAddDayToBillingEndAttribute()
    {
        $endDate = $this->parseEndDate();
        return $endDate->addDay()->toDateString();

    }

    public function getAddMonthToBillingEndAttribute()
    {
        $endDate = $this->parseEndDate();
        return $endDate->addMonth()->toDateString();

    }


    public static function currentDate()
    {
        return Carbon::today();
    }


    public function parseEndDate()
    {
        return Carbon::parse($this->billing_end);
    }
}
