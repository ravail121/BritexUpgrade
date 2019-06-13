<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class OrderGroupAddon extends Model
{
   protected $table = 'order_group_addon';

   protected $fillable = [
        'order_group_id', 'addon_id', 'prorated_amt', 'subscription_id', 'subscription_addon_id'
    ];

   public function ordergroup()
    {
     return $this->hasOne('App\Model\OrderGroup', 'id', 'order_group_id');
   }
   public function addon()
    {
     return $this->hasOne('App\Model\Addon', 'id', 'addon_id');
   }


// ----- Not touching the previously created code as they might be in use ----------

   public function orderGroupDetail()
   {
     return $this->belongsTo('App\Model\OrderGroup', 'order_group_id');
     
   }

    public function addonDetail()
   {
     return $this->belongsTo('App\Model\Addon', 'addon_id');
     
   }

}
