<?php
namespace App\Http\Controllers\Api\V1;


use Carbon\Carbon;
use App\Helpers\Log;
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
		$request->merge( [
			'company_id' => $request->get('company')->id,
		]);

		if(!$request->has('date')){
			$request->merge( [
				'date' => Carbon::now()->toDateTimeString()
			]);
		}
		try {
			$this->validate($request, [
				'customer_id'       => 'required',
				'subscription_id'   => 'required',
				'product_id'        => 'required',
				'old_product'       => 'required',
				'new_product'       => 'required'
			]);

			$subscription = SubscriptionLog::create($request->all());
			$response = [
				'status' => 'success',
				'data'   => $subscription
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