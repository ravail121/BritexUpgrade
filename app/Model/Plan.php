<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    protected $table = 'plan';
    public function order_group(){
    	return $this->belongsTo('App\Model\OrderGroup')->withTrashed();
    }
}
