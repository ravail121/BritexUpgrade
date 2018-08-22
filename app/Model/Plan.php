<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    protected $table = 'plan';
    public function order_group(){
    	return $this->belongsTo('App\Model\OrderGroup')->withTrashed();
    }

    public function device(){
    	return $this->hasOne('App\Model\Device', 'id');
    }

     public function device_to_plan()
    {
     return $this->hasMany('App\Model\DeviceToPlan', 'id');
   }
}
