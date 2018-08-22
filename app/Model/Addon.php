<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Addon extends Model
{
    protected $table = 'addon';

    public function plan_to_addon()
    {
     return $this->hasMany('App\Model\PlanToAddon', 'id');
   	}

}
