<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class PlanToAddon extends Model
{
  protected $table = 'plan_to_addon';   //

   public function plan()
    {
     return $this->hasOne('App\Model\Plan', 'id');
   }

   public function addon()
    {
     return $this->hasOne('App\Model\Addon', 'id');
   }
    
}
