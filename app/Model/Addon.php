<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Addon extends Model
{
    protected $table = 'addon';

    protected $fillable = [
        'hash', 'company_id', 'active_group_id',
    ];

    public function plan_to_addon()
    {
     return $this->hasMany('App\Model\PlanToAddon', 'id');
   	}

   	public function order_group_addon()
    {
     return $this->hasMany('App\Model\OrderGroupAddon', 'id');
   	}

}
