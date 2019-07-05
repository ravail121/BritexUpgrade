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
    public function password(Request $request)
    {
        $data=$request->validate([
            'identifier'     => 'required'
        ]);

        if ($this->isNumeric($data['identifier'])) {
            $customer = Customer::find($data['identifier']);
            if(!$customer){
                return $this->respond("Sorry Customer ID is not valid");
            }

            $email=$customer['email'];

        }else {
            $email=$data['identifier'];
            $count = Customer::whereEmail($email)->count();
            if($count < 1){
                return $this->respond("Sorry Email ID is not Valid");
            }
        }

        $customer = Customer::where('email', $email)->first();
        $request->headers->set('authorization', $customer->company->api_key);

        return $this->insertToken($email);
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


    protected function insertToken($email)
    {
        $hash = sha1(time());
        $user = [
                'email' => $email,
                'token' => $hash
            ];

        PasswordReset::create($user);
        event(new ForgotPassword($user));
        return $user;
    }


    public function resetPassword(Request $request)
    {
        $data=$request->validate([
            'token'      => 'required',
            'password'   => 'required|min:6',
        ]);

        $email=PasswordReset::whereToken($data['token'])->first();

        if(isset($email['email'])){
            $password['password'] = bcrypt($data['password']);
            Customer::whereEmail($email['email'])->update($password);
            PasswordReset::whereEmail($email['email'])->delete();
        }else{
            return $this->respond('Sorry Reset Password is no longer valid');
        }
    }
}
