<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Subscription extends Model
{
    protected $table = 'subscription';

   public function Customer()
    {
     return $this->belongs('App\Model\Customer', 'id');
   }
   public function SubscriptionAddon(){

   	return $this->hasmany('App\Model\SubscriptionAddon', 'id');
   }

}