<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    protected $table ='customer'; 
    protected $fillable=[
     'billing_address1', 'billing_address2', 'billing_city','billing_state_id','shipping_address1','shipping_address2','shipping_state_id','hash','shipping_city',
    ];

     public function company(){
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

     // public function pending_charge(){
     // 	return $this->belongsTo('App\Model\PendingCharge' , 'customer_id');
     // }
     public function tax(){
     	return $this->belongsTo('App\Model\Tax' , 'company_id');
     }
     public function coupon(){
        return $this->hasOne('App\Model\coupon', 'id');
     }
}
