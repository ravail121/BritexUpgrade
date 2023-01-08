<?php

namespace App\Http\Controllers\Api\V1;

use Validator;
use Carbon\Carbon;
use App\Helpers\Log;
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
		try {
			$requestCompany = $request->get( 'company' );

			$validator = Validator::make( $request->all(), [
				'identifier' => 'required'
			] );

			if ( $validator->fails() ) {
				$errors = $validator->errors();

				return $this->respondError( $errors->messages(), 422 );
			}

			$identifier = $request->get( 'identifier' );

			if ( $this->isNumeric( $identifier ) ) {
				$customer = Customer::find( $identifier );
				if ( ! $customer ) {
					return $this->respondError( "Sorry Customer ID is not valid" );
				}
				$email = $customer->email;
			} else {
				$email = $identifier;
				$count = Customer::whereEmail( $email )->where( 'company_id', $requestCompany->id )->count();
				if ( $count < 1 ) {
					return $this->respondError( "Sorry Email ID is not Valid" );
				}
			}

			$customer = Customer::where( 'email', $email )->where( 'company_id', $requestCompany->id )->first();
			$request->headers->set( 'authorization', $customer->company->api_key );

			return $this->insertToken( $email );
		} catch ( \Exception $e ) {
			Log::info($e->getMessage(), 'Error in forgot password');
			return $this->respondError( $e->getMessage() );
		}
    }

    /**
     * [isNumeric description]
     * @param  [type]  $value [description]
     * @return boolean        [return true for numeric value false for rest]
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
	        'created_at' => Carbon::now()
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
	public function resetPassword(Request $request) {
		try {
			$requestCompany = $request->get( 'company' );
			$validator = Validator::make( $request->all(), [
				'token'     => 'required',
				'password'  => 'required|min:6',
			] );

			if ( $validator->fails() ) {
				$errors = $validator->errors();

				return $this->respondError( $errors->messages(), 422 );
			}

			$token = $request->get( 'token' );

			$passwordReset = PasswordReset::where( [
				'token'      => $token,
				'company_id' => $requestCompany->id
			] )->first();

			if ( $passwordReset && $passwordReset->email ) {
				$password[ 'password' ] = bcrypt( $request->get('password') );
				Customer::where( [
					'email'      => $passwordReset->email,
					'company_id' => $requestCompany->id
				] )->update( $password );

				PasswordReset::where( [
					'token'      => $token,
					'company_id' => $requestCompany->id
				] )->delete();
				$successResponse = [
					'status'  => 'success',
					'message' => 'Password reset successfully, please login with your new password'
				];
				return $this->respond($successResponse);
			} else {
				return $this->respondError( 'Sorry Reset Password is no longer valid' );
			}
		} catch ( \Exception $e ) {
			Log::info($e->getMessage(), 'Error in password reset');
			return $this->respond( $e->getMessage() );
		}
	}
}
