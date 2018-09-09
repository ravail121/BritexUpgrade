<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class SubscriptionAddon extends Model
{
	protected $table ='subscription_addon';
    public function Subscription(){
   		return response()->json('App\Model\Subscription','id');
   }
}
