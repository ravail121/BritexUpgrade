<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Subscription extends Model
{
    protected $table = 'subscription';

    protected $fillable = [
      'order_id',
      'customer_id',
      'plan_id',
      'phone_number',
      'status',
      'suspend_restore_status',
      'upgrade_downgrade_status',
      'upgrade_downgrade_date_submitted',
      'port_in_progress',
      'sim_id',
      'sim_name',
      'sim_card_num',
      'old_plan_id',
      'new_plan_id',
      'downgrade_date',
      'tracking_num',
      'device_id',
      'device_os',
      'device_imei',
      'subsequent_porting',
      'requested_area_code',
      'ban_id',
      'ban_group_id',
      'activation_date',
      'suspended_date',
      'closed_date',
    ];

   public function Customer()
    {
     return $this->hasOne('App\Model\Customer', 'id');
   }

  public function subscription_addon(){

  	return $this->hasmany('App\Model\SubscriptionAddon', 'id');
  }

  public function subscriptionAddon(){

    return $this->hasMany('App\Model\SubscriptionAddon', 'subscription_id', 'id');
  }


  public function plan(){
  	return $this->hasone('App\Model\Plan', 'id', 'plan_id');
  }

  public function device(){
    return $this->hasone('App\Model\Device', 'id', 'device_id');
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