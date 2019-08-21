<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use App\libs\Constants\ConstantInterface;

class Device extends Model implements ConstantInterface
{
    protected $table = 'device';

    /**
     * Visible Devices
     * (Similar function in Sim model as well)
     * @param  [type] $query
     * @return 
     */
    public function scopeVisible($query)
    {
        return $query->whereIn('show', [
            self::SHOW_COLUMN_VALUES['visible-and-orderable'],
            self::SHOW_COLUMN_VALUES['visible-and-unorderable'],
        ]);
    }

    public function order_group()
    {
        return $this->belongsTo('App\Model\OrderGroup')->withTrashed();
    }
    
    // Needs to be deleted
    // ----------------------------------------------------------
    public function plan()
    {
        return $this->belongsTo('App\Model\Plan')->withTrashed();
    }
    //-----------------------------------------------------------
    
    
    public function plans()
    {
        return $this->belongsToMany('App\Model\Plan', 'device_to_plan', 'device_id', 'plan_id');
    }
    
    public function company()
    {
        return $this->hasOne('App\Model\Company');
    }
    
    public function customerStandaloneDevice()
    {
        return $this->hasOne('App\Model\CustomerStandaloneDevice', 'device_id');
    }
    
    public function device_image()
    {
        return $this->hasMany('App\Model\DeviceToImage', 'device_id');
    }
    
    public function device_to_carrier()
    {
        return $this->hasMany('App\Model\DeviceToCarrier', 'id');
    }
    
    public function device_to_plan()
    {
        return $this->hasMany('App\Model\DeviceToPlan', 'id');
    }
    
    public function device_to_sim()
    {
        return $this->hasMany('App\Model\DeviceToSim', 'id');
    }

    public static function getDeviceName($id)
    {
        return self::find($id)->name;
    }

    public static function deviceWithSubscriptionCharges($id)
    {
        return self::find($id)->amount_w_plan;
    }
}