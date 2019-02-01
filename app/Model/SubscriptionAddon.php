<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class SubscriptionAddon extends Model
{
	protected $table ='subscription_addon';
	protected $fillable = [ 'subscription_id', 'addon_id', 'status', 'removal_date'];

    public function subscription(){
    		return $this->hasOne('App\Model\Subscription' , 'id');
    }
   
}
