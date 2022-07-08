<?php
namespace App\Http\Controllers\Api\V1;


use Carbon\Carbon;
use App\Helpers\Log;
use App\Model\Subscription;
use Illuminate\Http\Request;
use App\Model\SubscriptionLog;
use App\Http\Controllers\BaseController;

/**
 *
 */
class SubscriptionLogController extends BaseController
{
	public function store(Request $request)
	{
		try {
			$this->validate($request, [
				'subscription_id'   => 'required|exists:subscription,id',
			]);

			$subscription = Subscription::find($request->get('subscription_id'));

			$request->merge( [
				'company_id' => $subscription->company_id,
				'customer_id' => $subscription->customer_id
			]);

			if(!$request->has('date')){
				$request->merge( [
					'date' => Carbon::now()->toDateTimeString()
				]);
			}

			$subscriptionLog = SubscriptionLog::create($request->all());
			$response = [
				'status' => 'success',
				'data'   => $subscriptionLog
			];
			return $this->respond( $response );
		} catch (\Exception $e) {
			Log::info($e->getMessage() . ' on line number: '.$e->getLine() . ' Create Subscription Log');
			$response = [
				'status' => 'error',
				'data'   => $e->getMessage()
			];
			return $this->respond( $response, 400 );
		}
	}

}