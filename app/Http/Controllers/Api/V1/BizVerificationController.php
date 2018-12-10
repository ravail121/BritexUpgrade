<?php

namespace App\Http\Controllers\Api\V1;
use Validator;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use App\Http\Controllers\Controller;
use App\Http\Controllers\BaseController;
use App\Model\BusinessVerification;
use App\Model\BusinessVerificationDocs;

class BizVerificationController extends BaseController
{ 
     public function __construct(){
        $this->content = array();
    }


    public function post(Request $request){
    	$data = $request->all();
      //var_dump($data);
      
      $invalidate = $this->validate_input($data, [
       'order_hash'=>'required|string',
       'doc_file'=>'file',
       'business_name'=>'string',
       'tax_id'=> 'numeric',
       'fname'=> 'string',
       'lname'=>'string',
       'email'=>'string',
       'address_line1'=>'string',
       'city'=>'string',
       'state'=>'string',
       'zip'=>'numeric'
       
      ] );


      if($invalidate){
        return $invalidate;
      }

      // if(isset($data['doc_file'])){
      //    $Destinationpath = 'BusinessVerificationDocs';
      //    $file->move($Destinationpath ,$file->getClientOriginalName());
      // }

      // else{
      //  	$businessVerification = BusinessVerification::create([     
      //   'business_name'=>$data['business_name'],
      //   'tax_id'=>$data['tax_id'],
      //   'fname'=>$data['fname'],
      //   'lname'=>$data['lname'],
      //   'email'=>$data['email'],
      //   'hash'=> sha1(time())

      //  	]);
      //  	$businesVerificationDocs = BusinessVerificationDocs::create(['bus_ver_id'=>$businessVerification->id]);
      //   return $this->respond(['bus_ver_id',$businessVerification->id]);
      // }
      return $this->respond([]);
      
    }

}