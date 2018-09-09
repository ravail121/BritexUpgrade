<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class BusinessVerificationDocs extends Model
{
	protected $table = 'business_verification_docs';
	protected $fillable = ['bus_ver_id'];
   
    public function bizverification(){
    	return $this->belongsTo('App\Model\BusinessVerification', 'id')->withTrashed();
    }
}