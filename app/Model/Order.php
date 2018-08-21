<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $table = 'order';

    public function order_group(){
    	return $this->belongsTo('App\Model\OrderGroup')->withTrashed();
    }
      
}
