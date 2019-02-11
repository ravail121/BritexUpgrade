<?php

namespace App\Http\Controllers\Api\V1;

use App\PasswordReset;
use App\Model\Customer;
use Illuminate\Http\Request;
use App\Events\ForgotPassword;
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
        }else{
            $count = Customer::whereEmail($email)->count();
            if($count < 1){
                return $this->respondError("Invalid Email ID");
            }
        }
        $this->insertToken($email);
    }


    protected function insertToken($email)
    {
        $hash = sha1(time());
        $user = [
                'email' => $email,
                'token' => $hash
            ];
        PasswordReset::create($user);
        event(new ForgotPassword($user));
    }
}
