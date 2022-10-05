<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class DeviceType extends Model
{
  protected $table = 'device_type';   //

  public function device()
    {
     return $this->hasOne('App\Model\Device', 'id');
   }
    
}
