<?php

namespace App\Model;

use App\Model\SystemGlobalSetting;
use App\Model\BusinessVerification;
use Illuminate\Database\Eloquent\Model;

class BusinessVerificationDocs extends Model
{
	protected $table = 'business_verification_doc';
	protected $fillable = ['bus_ver_id','src'];
   
    public function bizverification(){
        // return $this->belongsTo('App\Model\BusinessVerification')->withTrashed();
    	return $this->belongsTo('App\Model\BusinessVerification');
    	
    }

    protected $attributes = [
        'bus_ver_id' => null
        //'address_line1' => 'nddfhf',
    ];


    public static function directoryLocation($companyId = null, $businessVerificationId = null)
    {
        $directoryLocation = SystemGlobalSetting::first()->upload_path .'/uploads/';
        
        if($companyId){
            $directoryLocation .= "{$companyId}/bus_ver/";
        }

        if ($businessVerificationId) {
            $directoryLocation .= "{$businessVerificationId}/";
        }


        return $directoryLocation; 
    }

    public function getPathAttribute()
    {

        return self::directoryLocation($this->bizverification->order->company_id, $this-$this->bizverification->id)  .'/' . $this->src;
    }



    public static function siteUrlLocation($companyId = null, $businessVerificationId = null)
    {
        $siteUrlLocation = SystemGlobalSetting::first()->site_url .'/uploads/';
        
        if($companyId){
            $siteUrlLocation .= "{$companyId}/bus_ver/";
        }

        if ($businessVerificationId) {
            $siteUrlLocation .= "{$businessVerificationId}/";
        }


        return $siteUrlLocation; 
    }

    public function getUrlAttribute()
    {

        return self::siteUrlLocation($this->bizverification->order->company_id, $this-$this->bizverification->id)  .'/' . $this->src;
    }






    // public static function directoryLocation()
    // {
    //     return config('custom.location_documents_uploads');
    // }
}