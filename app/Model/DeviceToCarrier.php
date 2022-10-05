<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class DeviceToCarrier extends Model
{
  protected $table = 'device_to_carrier';   //

   public function device()
    {
     return $this->hasOne('App\Model\Device', 'id');
   }
    
}
