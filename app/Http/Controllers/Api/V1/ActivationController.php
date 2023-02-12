<?php

namespace App\Http\Controllers\Api\V1;

use App\Helpers\Log;
use App\Model\Subscription;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Events\ActivationError;
use App\Http\Controllers\BaseController;
use Illuminate\Support\Facades\Validator;
/**
 * Class ActivationController
 *
 * @package App\Http\Controllers\Api\V1
 */
class ActivationController extends BaseController
{
	public function sendNotification(Request $request)
	{
		try {
			$requestCompany = $request->get( 'company' );
			$validation     = Validator::make( $request->all(), [
				'message'           => 'required',
				'subscription_id'  => [
					'required',
					'numeric',
					Rule::exists('subscription', 'id')->where(function ($query) use ($requestCompany){
						return $query->where([
							['status', 'for-activation'],
							['company_id', $requestCompany->id]
						]);
					})
				],
			] );

			if ( $validation->fails() ) {
				$errors                  = $validation->errors();
				$validationErrorResponse = [
					'status' => 'error',
					'data'   => $errors->messages()
				];

				return response()->json( $validationErrorResponse, 422 );
			}

			$subscription = Subscription::find($request->get('subscription_id'));

			event( new ActivationError( $subscription, $request->get( 'message' ) ) );

			$successResponse = [
				'status' => 'success',
				'data'   => 'Notification sent successfully'
			];

			return $this->respond( $successResponse );
		} catch (\Exception $e){
			Log::info($e->getMessage(), 'Error in activation error send notification');
			return $this->respondError($e->getMessage());
		}
	}
}