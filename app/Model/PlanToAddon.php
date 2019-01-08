<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class PlanToAddon extends Model
{
  protected $table = 'plan_to_addon';   //

   public function plan()
    {
     return $this->hasOne('App\Model\Plan', 'id', 'plan_id');
   }

   public function addon()
    {
     return $this->hasOne('App\Model\Addon', 'id', 'addon_id');
   }

    public function addonDetails()
    {
     return $this->belongsTo('App\Model\Addon', 'addon_id');
   }

   public function planDetails()
    {
     return $this->belongsTo('App\Model\Plan', 'plan_id');
   }

    
}
