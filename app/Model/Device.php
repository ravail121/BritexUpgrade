<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Device extends Model
{
  protected $table = 'device';   //

   public function order_group(){
    	return $this->belongsTo('App\Model\OrderGroup')->withTrashed();
    }
    
}
