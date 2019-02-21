<?php

namespace App\Model;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class SubscriptionAddon extends Model
{
	protected $table ='subscription_addon';
	protected $fillable = [ 'subscription_id', 'addon_id', 'status', 'removal_date'];

    public function subscription(){
    		return $this->hasOne('App\Model\Subscription' , 'id');
    }

    public function subscriptionDetail(){
    		return $this->belongsTo('App\Model\Subscription');
    }


    public function scopeTodayEqualsRemovalDate($query)
    {
    	$today = Carbon::today();
        return $query->where('removal_date', $today->toDateString());
    }
   
}
