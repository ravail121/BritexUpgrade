<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    protected $table = 'plan';
    public function order_group(){
    	return $this->belongsTo('App\Model\OrderGroup')->withTrashed();
    }

    public function company(){
    	return $this->hasOne('App\Model\Company', 'id', 'company_id');
    }

     public function device_to_plan()
    {
     return $this->hasMany('App\Model\DeviceToPlan', 'id');
   }
}
