<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class PendingCharge extends Model
{
    protected $table = 'pending_charge';


    public function Customer()
    {
     return $this->hasOne('App\Model\Customer', 'id');
   	}
}
