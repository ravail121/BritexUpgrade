<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Device extends Model
{
  protected $table = 'device';   //

   public function order_group(){
    	return $this->belongsTo('App\Model\OrderGroup')->withTrashed();
    }

    public function plan(){
    	return $this->belongsTo('App\Model\Plan')->withTrashed();
    }

    public function company(){
    	return $this->hasOne('App\Model\Company');
    }

    public function device_image()
    {
     return $this->hasMany('App\Model\DeviceToImage', 'id');
   }

   public function device_to_carrier()
    {
     return $this->hasMany('App\Model\DeviceToCarrier', 'id');
   }

   public function device_to_plan()
    {
     return $this->hasMany('App\Model\DeviceToPlan', 'id');
   }
    
  public function device_to_sim()
    {
     return $this->hasMany('App\Model\DeviceToSim', 'id');
   }
}
