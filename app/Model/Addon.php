<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Model\Addon
 */
class Addon extends Model
{

	/**
	 * @var string
	 */
    protected $table = 'addon';

	/**
	 * @var string[]
	 */
    protected $fillable = [
        'hash',
	    'company_id',
	    'active_group_id',
	    'is_one_time'
    ];

	/**
	 * @return \Illuminate\Database\Eloquent\Relations\HasMany
	 */
    public function plan_to_addon()
    {
     return $this->hasMany('App\Model\PlanToAddon', 'id');
   	}

<<<<<<< HEAD
=======
	/**
	 * @return \Illuminate\Database\Eloquent\Relations\HasMany
	 */
>>>>>>> Britex/develop
   	public function order_group_addon()
    {
     return $this->hasMany('App\Model\OrderGroupAddon', 'id');
   	}



<<<<<<< HEAD
// ----- Not touching the previously created code as they might be in use ----------
=======
	// ----- Not touching the previously created code as they might be in use ----------
>>>>>>> Britex/develop

    public function orderGroups()
    {
     return $this->belongsToMany('App\Model\OrderGroup', 'order_group_addon', 'addon_id', 'order_group_id');
    }

    public function planToAddonDetails()
    {
     return $this->belongsToMany('App\Model\PlanToAddon');
    }

<<<<<<< HEAD
// ----- Not touching the previously created code as they might be in use ----------
=======
	// ----- Not touching the previously created code as they might be in use ----------
>>>>>>> Britex/develop

    public function subscriptionAddon()
    {
        return $this->hasMany(App\Model\SubscriptionAddon::class, 'addon_id', 'id');
    }
<<<<<<< HEAD
=======

	public function scopeOneTime($query)
	{
		return $query->where('is_one_time', 1);
	}
>>>>>>> Britex/develop
}
