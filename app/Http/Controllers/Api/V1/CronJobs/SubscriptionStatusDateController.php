<?php

namespace App\Http\Controllers\Api\V1\CronJobs;

use App\Model\Customer;
use Illuminate\Http\Request;
use App\Events\ReportNullSubscriptionStartData;

/**
 * Class UpdateController
 *
 * @package App\Http\Controllers\Api\V1\CronJobs
 */
class SubscriptionStatusDateController extends Controller
{
	/**
	 * @param $request
	 */
	public function processSuspensions(Request $request)
	{
		try{
			$customers = Customer::whereNull('subscription_start_data')->where(['account_suspended', false])->get();

			$customerCount = $customers->count();

			if($customerCount) {
				event( new ReportNullSubscriptionStartData( $customers ) );
			}
		} catch (Exception $e) {
			\Log::info($e->getMessage(). ' on the line '. $e->getLine());
		}
	}

}
