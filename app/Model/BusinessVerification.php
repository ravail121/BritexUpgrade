<?php

namespace App\Model;
use Illuminate\Notifications\Notifiable;

//use App\Model\BusinessVerification;
use Illuminate\Database\Eloquent\Model;

class BusinessVerification extends Model
{  
    use Notifiable;

	protected $table = 'business_verification'  ;
    
	protected $fillable =[ 'order_id', 'approved' , 'hash' ,'tax_id', 'fname', 'lname', 'email' , 'business_name' ];

    protected $attributes = [
        'approved' => 0,
    ];

    // protected $attributes = [
    //     'approved' => 0,
    //     'address_line1' => 'nddfhf',
    //     'address_line2' => 'dfhddh',
    //     'city' => 'dggdh',
    //     'state' => 'hchdhs',
    //     'zip' => 'hchsfv',
    //     'business_name' => null,
    //     'fname' => null,
    //     'lname' => null,
    //     'email' => null,
    //     'tax_id' => null,
        
    // ];
    

    public function bizverificationDocs(){
        // return $this->hasOne('App\Model\BusinessVerification','bus_ver_id','id');
        return $this->hasOne('App\Model\BusinessVerification');
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    // public function checkoutUrl()
    // {
    //     return config('custom.checkout_url');
    // }
    	
    
}