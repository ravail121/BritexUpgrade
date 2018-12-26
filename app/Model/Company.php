<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    protected $table = 'company';

    public function device(){
    	return $this->belongsTo('App\Model\Device')->withTrashed();
    }

}
