<?php

namespace App\Http\Controllers\Api\V1;

use App\Model\Customer;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Controllers\BaseController;

class ForgotPasswordController extends BaseController
{
    public function password(Request $request){
    	$email = $request->email;
    	if (filter_var($email, FILTER_VALIDATE_INT)) {
            $mail = Customer::find($email);
            if(!isset($mail['email'])){
                return $this->respondError("Invalid Customer ID");
            }
            $email=$mail['email'];
        }
        $hash = sha1(time());
        dd($hash);
    }
}
