<?php

namespace App\Http\Controllers\Api\V1;

use App\PasswordReset;
use App\Model\Customer;
use Illuminate\Http\Request;
use App\Events\ForgotPassword;
use App\Http\Controllers\BaseController;

/**
 * Class ForgotPasswordController
 *
 * @package App\Http\Controllers\Api\V1
 */
class ForgotPasswordController extends BaseController
{
	/**
	 * @param Request $request
	 *
	 * @return array|\Illuminate\Http\JsonResponse
	 */
	public function password(Request $request)
    {
	    $requestCompany = $request->get('company');
        $data = $request->validate([
            'identifier'     => 'required'
        ]);

        if ($this->isNumeric($data['identifier'])) {
            $customer = Customer::find($data['identifier']);
            if(!$customer){
                return $this->respond("Sorry Customer ID is not valid");
            }

            $email = $customer['email'];

        }else {
            $email = $data['identifier'];
            $count = Customer::whereEmail($email)->where('company_id', $requestCompany->id)->count();
            if($count < 1){
                return $this->respond("Sorry Email ID is not Valid");
            }
        }

        $customer = Customer::where('email', $email)->where('company_id', $requestCompany->id)->first();
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


	/**
	 * @param $email
	 *
	 * @return array
	 */
	protected function insertToken($email)
    {
        $hash = sha1(time());
        $user = [
            'email'      => $email,
            'token'      => $hash,
            'company_id' => \Request::get('company')->id,
        ];

        PasswordReset::create($user);
        event(new ForgotPassword($user));
        return $user;
    }


	/**
	 * @param Request $request
	 *
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function resetPassword(Request $request)
    {
        $data = $request->validate([
            'token'      => 'required',
            'password'   => 'required|min:6',
        ]);
        $companyId = $request->get('company')->id;

        $passwordReset = PasswordReset::where([
            'token'         => $data['token'],
            'company_id'    => $companyId,
        ])->first();

        if(isset($passwordReset['email'])){
            $password['password'] = bcrypt($data['password']);
            Customer::where([
                'email'         => $passwordReset['email'],
                'company_id'    => $companyId
            ])->update($password);

            PasswordReset::where([
                'token'         => $data['token'],
                'company_id'    => $companyId,
            ])->delete();
        }else{
            return $this->respond('Sorry Reset Password is no longer valid');
        }
    }
}
