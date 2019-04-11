<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    protected $table = 'company';

    public function device(){
    	return $this->belongsTo('App\Model\Device')->withTrashed();
    }

    public function devices()
    {
        return $this->hasMany('App\Model\Device', 'company_id', 'id');
    }

    public function visibleDevices()
    {
        return $this->devices()->visible()->orderBy('device.sort');
    }

    public function sims()
    {
        return $this->hasMany('App\Model\Sim', 'company_id', 'id');
    }

    public function visibleSims()
    {
        return $this->sims()->visible();
    }

    public function order()
    {
        return $this->belongsTo('App\Model\Order');
    }

}
