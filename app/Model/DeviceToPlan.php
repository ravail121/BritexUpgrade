<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class DeviceToPlan extends Model
{
  protected $table = 'device_to_plan';   //

   public function device()
   {
     return $this->hasOne('App\Model\Device', 'id');
   }

   public function plan()
   {
        return $this->belongsTo('App\Model\Plan', 'plan_id', 'id');
   }
    
}
