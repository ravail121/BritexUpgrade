<?php
namespace App\Http\Controllers\Api\V1;

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
		try {
			$this->validate($request, [
				'date'          => 'required',
				'category'      => 'required',
				'product_id'    => 'required',
				'description'   => 'required',
				'old_product'   => 'required',
				'new_product'   => 'required'
			]);
			$subscription = SubscriptionLog::create($request->all());
			$response = [
				'status' => 'error',
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