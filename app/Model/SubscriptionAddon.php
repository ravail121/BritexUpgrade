<?php

namespace App\Model;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class SubscriptionAddon extends Model
{
    const STATUSES = [
        'active'            => 'active',
        'for-adding'        => 'for-adding',
        'removal-scheduled' => 'removal-scheduled',
        'for-removal'       => 'for-removal',
        'removed'           => 'removed'
    ];

	protected $table ='subscription_addon';
	protected $fillable = [ 'subscription_id', 'addon_id', 'status', 'removal_date', 'date_submitted'];

    public function subscription(){
    		return $this->hasOne('App\Model\Subscription' , 'id');
    }

    public function subscriptionDetail(){
    	return $this->belongsTo('App\Model\Subscription', 'subscription_id', 'id');
    }

    public function scopeTodayEqualsRemovalDate($query)
    {
    	$today = Carbon::today();
        return $query->where('removal_date', $today->toDateString());
    }

    public function scopeNotRemoved($query)
    {
        return $query->whereNotIn('status', ['removed']);
    }

    public function scopeBillable($query)
    {
        return $query->whereIn('status', [
            self::STATUSES['active'],
            self::STATUSES['for-adding']
        ]); 
    }

    public function addon()
    {
        return $this->belongsTo(Addon::class, 'addon_id', 'id');  
    }

    public function isBillable()
    {
        return in_array($this->status, [
            self::STATUSES['active'],
            self::STATUSES['for-adding']
        ]);
    }

    public function shouldBeRemoved()
    {
        return in_array($this->status, [
            self::STATUSES['removal-scheduled'],
            self::STATUSES['for-removal']
        ]);
    }

    public function addons()
    {
        return $this->belongsTo('App\Model\Addon', 'addon_id');
    }
}
