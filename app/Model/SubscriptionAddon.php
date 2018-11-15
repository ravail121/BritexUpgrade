<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class SubscriptionAddon extends Model
{
	protected $table ='subscription_addon';
	protected $fillable = [ 'Subscription_id'];

    public function subscription(){
    		return $this->hasOne('App\Model\Subscription' , 'id');
    }
   
}
