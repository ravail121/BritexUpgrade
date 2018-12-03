<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class OrderGroupAddon extends Model
{
   protected $table = 'order_group_addon';

   protected $fillable = [
        'order_group_id', 'addon_id',
    ];

   public function ordergroup()
    {
     return $this->hasOne('App\Model\OrderGroup', 'id');
   }
   public function addon()
    {
     return $this->hasOne('App\Model\Addon', 'id', 'addon_id');
   }

}
