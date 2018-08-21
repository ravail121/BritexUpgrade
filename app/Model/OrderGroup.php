<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class OrderGroup extends Model
{
   protected $table = 'order_group';

   public function order()
    {
     return $this->hasOne('App\Model\Order', 'id');
   }
   public function sim()
    {
     return $this->hasOne('App\Model\Sim', 'id');
   }
   public function device()
    {
     return $this->hasOne('App\Model\Device', 'id');
   }
   public function plan()
    {
     return $this->hasOne('App\Model\Plan', 'id');
   }
}
