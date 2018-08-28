<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class DeviceToSim extends Model
{
  protected $table = 'device_to_sim';   //

   public function device()
    {
     return $this->hasOne('App\Model\Device', 'id');
   }

   public function sim()
    {
     return $this->hasOne('App\Model\Sim', 'id');
   }
    
}
