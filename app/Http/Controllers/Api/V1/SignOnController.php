<?php

namespace App\Http\Controllers\Api\V1;

use Auth;
use App\Model\Customer;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Controllers\BaseController;

class SignOnController extends BaseController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function signOn(Request $request)
    {
        $userdata=$request->validate([
            'email'   => 'required',
            'password'   => 'required',
            ]);
        if (filter_var($userdata['email'], FILTER_VALIDATE_INT)) {
            $mail = Customer::find($userdata['email']);
            if(!isset($mail['email'])){
                return $this->respondError("Invalid Customer ID");
            }
            $userdata['email']=$mail['email'];
        }     
        if(Auth::validate($userdata))
        {
            $user = Customer::whereEmail($userdata['email'])->get(['id','hash']);
            return $this->respond($user[0]);
        }
        else{
            return $this->respondError("Invalid Email or Password");
        }
    }
}
