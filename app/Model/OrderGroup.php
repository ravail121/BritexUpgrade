<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class OrderGroup extends Model
{
   protected $table = 'order_group';

   protected $fillable = [
        'order_id', 'device_id', 'sim_id', 'plan_id', 'sim_num', 'sim_type', 'porting_number', 'area_code'
    ];

   public function order()
    {
     return $this->hasOne('App\Model\Order', 'id', 'order_id');
   }
   public function sim()
    {
     return $this->hasOne('App\Model\Sim', 'id', 'sim_id');
   }
   public function device()
    {
     return $this->hasOne('App\Model\Device', 'id', 'device_id');
   }
   public function plan()
    {
     return $this->hasOne('App\Model\Plan', 'id', 'plan_id');
   }

   public function order_group_addon()
    {
     return $this->hasMany('App\Model\OrderGroupAddon');
    }



// ----- Not touching the previously created code as they might be in use ----------

    public function addons()
    {
     return $this->belongsToMany('App\Model\Addon');
    }

// ----- Not touching the previously created code as they might be in use ----------



    // this is a recommended way to declare event handlers
    public static function boot() {
        parent::boot();

        static::deleting(function($ordergroup) { // before delete() method call this
             $ordergroup->order_group_addon()->delete();
             // do the rest of the cleanup...
        });
    }
}
