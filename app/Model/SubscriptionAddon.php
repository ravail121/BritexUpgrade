<?php

namespace App\Model;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * class SubscriptionAddon
 */
class SubscriptionAddon extends Model
{

	/**
	 * @var string[]
	 */
    const STATUSES = [
        'active'            => 'active',
        'for-adding'        => 'for-adding',
        'removal-scheduled' => 'removal-scheduled',
        'for-removal'       => 'for-removal',
        'removed'           => 'removed'
    ];

	/**
	 * @var string
	 */
	protected $table ='subscription_addon';

	/**
	 * @var string[]
	 */
	protected $fillable = [ 'subscription_id', 'addon_id', 'status', 'removal_date', 'date_submitted'];

	/**
	 * @return \Illuminate\Database\Eloquent\Relations\HasOne
	 */
    public function subscription(){
		return $this->hasOne('App\Model\Subscription' , 'id');
    }

	/**
	 * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
	 */
    public function subscriptionDetail(){
    	return $this->belongsTo('App\Model\Subscription', 'subscription_id', 'id');
    }

	/**
	 * @param $query
	 *
	 * @return mixed
	 */
    public function scopeTodayEqualsRemovalDate($query)
    {
    	$today = Carbon::today();
        return $query->where('removal_date', $today->toDateString());
    }

	/**
	 * @param $query
	 *
	 * @return mixed
	 */
    public function scopeNotRemoved($query)
    {
        return $query->whereNotIn('status', ['removed']);
    }

	/**
	 * @param $query
	 *
	 * @return mixed
	 */
    public function scopeBillable($query)
    {
        return $query->whereIn('status', [
            self::STATUSES['active'],
            self::STATUSES['for-adding']
        ]); 
    }

	/**
	 * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
	 */
    public function addon()
    {
        return $this->belongsTo(Addon::class, 'addon_id', 'id');  
    }

	/**
	 * @return bool
	 */
    public function isBillable()
    {
        return in_array($this->status, [
            self::STATUSES['active'],
            self::STATUSES['for-adding']
        ]);
    }

	/**
	 * @return bool
	 */
    public function shouldBeRemoved()
    {
        return in_array($this->status, [
            self::STATUSES['removal-scheduled'],
            self::STATUSES['for-removal']
        ]);
    }

	/**
	 * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
	 */
    public function addons()
    {
        return $this->belongsTo('App\Model\Addon', 'addon_id');
    }
}
