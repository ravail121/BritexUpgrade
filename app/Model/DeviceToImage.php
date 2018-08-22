<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class DeviceImage extends Model
{

  protected $table = 'device_image';   //

   public function device()
    {
     return $this->hasOne('App\Model\Device', 'id');
   }
    

}
