<?php

namespace App\Model;

//use App\Model\BusinessVerification;
use Illuminate\Database\Eloquent\Model;

class BusinessVerificationDocs extends Model
{
	protected $table = 'business_verification_doc';
	protected $fillable = ['bus_ver_id','src'];
   
    public function bizverification(){
    	return $this->belongsTo('App\Model\BusinessVerification')->withTrashed();
    	
    }

    protected $attributes = [
        'bus_ver_id' => null
        //'address_line1' => 'nddfhf',
    ];

    public function path()
    {
        //
    }



    // public function documentPath()
    // {
    //     return public_path(self::directoryLocation() . DIRECTORY_SEPARATOR . $this->src);
    // }


    // public static function directoryLocation()
    // {
    //     return config('custom.location_documents_uploads');
    // }
}