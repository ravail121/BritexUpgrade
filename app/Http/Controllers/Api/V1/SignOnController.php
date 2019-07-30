<?php

namespace App\Http\Controllers\Api\V1;

use Auth;
use App\Model\Customer;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Controllers\BaseController;

class SignOnController extends BaseController
{
    
    public function signOn(Request $request)
    {
        $data = $request->validate([
            'identifier' => 'required',
            'password'   => 'required',
        ]);

        if ($this->isNumeric($data['identifier'])) {
            $customer = Customer::find($data['identifier']);

            if(!$customer){
                return $this->respondError("Invalid Customer ID");
            }

            $data['email'] = $customer->email;
        }else {
            $data['email'] = $data['identifier'];
            $customer = Customer::whereEmail($data['email'])->first();
        }

        $companyId = \Request::get('company')->id;

        if($customer->company_id != $companyId){
            return $this->respondError("Invalid Company ID");
        }

        unset($data['identifier']);
          
        if(Auth::validate($data))
        {
            $user = Customer::whereEmail($data['email'])->get(['id','hash']);
            return $this->respond($user[0]);
        }
        else{
            return $this->respondError("Invalid Email or Password");
        }
    }


    /**
     * [isNumeric description]
     * @param  [type]  $value [description]
     * @return boolean        [return true for nunmaric value false for rest]
     */
    private function isNumeric($value)
    {
        return (filter_var($value, FILTER_VALIDATE_INT));
    }
}