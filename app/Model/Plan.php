<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    const REGULATORY_FEE_TYPES = [
        'fixed_amount'            => 1,
        'percentage_of_plan_cost' => 2
    ];

    protected $table = 'plan';

    public function order_group(){
    	return $this->belongsTo('App\Model\OrderGroup')->withTrashed();
    }

    public function company(){
    	return $this->hasOne('App\Model\Company', 'id', 'company_id');
    }

    public function subscription(){
        return $this->hasOne('App\Model\Subscription','plan_id');
    }

    public function subscriptions(){
        return $this->hasMany('App\Model\Subscription','plan_id');
    }
    
    public function device_to_plan()
    {
        return $this->hasMany('App\Model\DeviceToPlan', 'id');
    }

    public function devices()
    {
        return $this->belongsToMany('App\Model\Device', 'device_to_plan', 'plan_id', 'device_id');
    }

    public function planToAddon()
    {
        return $this->belongsToMany('App\Model\PlanToAddon');
    }

    public static function getRegualtoryAmount($id, $planAmount)
    {
        $plan = self::find($id);
        if ($plan->regulatory_fee_amount) {
            if ($plan->regulatory_fee_type == self::REGULATORY_FEE_TYPES['percentage_of_plan_cost']) {
                return $plan->regulatory_fee_amount * $planAmount / 100;
            } else {
                return $plan->regulatory_fee_amount;
            }
        }
        return 0;
    }

    public function carrier()
    {
        return $this->belongsTo('App\Model\Carrier');
    }

}
