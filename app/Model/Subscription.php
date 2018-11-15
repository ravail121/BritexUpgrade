<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Subscription extends Model
{
    protected $table = 'subscription';
    protected$fillable =[
    'customer_id', 'plan_id', 'status', 'suspend_restore_status', 'upgrade_downgrade_status', 'porting_status', 'sim_card_num', 'old_plan_id', 'new_plan_id', 'downgrade_date','subsequent_porting','updated_at', 'created_at','phone_number', 'sim_card_product_id','imei',
    ];

   public function Customer()
    {
     return $this->hasOne('App\Model\Customer', 'id');
   }

   public function subscription_addon(){

   	return $this->hasmany('App\Model\SubscriptionAddon', 'id');
   }
  public function plan(){
  	return $this->hasone('App\Model\Plan', 'id');
  }

  public function new_plan(){
    return $this->hasone('App\Model\Plan', 'id' );
  }

  public function plans(){
    return $this->belongsTo('App\Model\plan', 'plan_id');
  }

  
 
 public function addon(){
      return $this->belongsTo('App\Model\Addon' , 'id');

    }
  public function subscription_coupon(){
    return $this->belongsTo('App\Model\SubscriptionCoupon', 'subscription_id');
  }
}