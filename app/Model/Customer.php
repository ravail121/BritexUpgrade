<?php

namespace App\Model;

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

    public function credit()
    {
        return $this->belongsTo(Credit::class);
    }
}
