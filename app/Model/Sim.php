<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use App\libs\Constants\ConstantInterface;

class Sim extends Model implements ConstantInterface
{
    protected $table = 'sim';

    /**
     * Visible Sims
     * (Similar function in Device model as well)
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

    public function device_to_sim()
    {
    	return $this->hasMany('App\Model\DeviceToSim', 'id');
   	}

   public function customerStandaloneSim()
   {
        return $this->hasOne('App\Model\CustomerStandaloneSim','sim_id', 'id');
   }

   public static function getSimName($id)
   {
        return self::find($id)->name;
   }

   public static function getSimCharges($id)
   {
        return self::find($id)->amount_w_plan;
   }
}
