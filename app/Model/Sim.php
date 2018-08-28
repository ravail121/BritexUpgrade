<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Sim extends Model
{
     protected $table = 'sim';

     public function order_group(){
     	return $this->belongsTo('App\Model\OrderGroup')->withTrashed();
    }

    public function device_to_sim()
    {
     return $this->hasMany('App\Model\DeviceToSim', 'id');
   }
}
