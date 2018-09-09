<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class BusinessVerification extends Model
{  
	protected $table = 'business_verification'  ;
	protected $fillable =[ 'tax_id', 'fname', 'lname', 'email' , 'business_name'];
   

}