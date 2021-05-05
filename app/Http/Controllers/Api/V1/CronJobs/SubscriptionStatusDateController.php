<?php

namespace App\Http\Controllers\Api\V1\CronJobs;

use App\Model\Customer;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Builder;
use App\Events\ReportNullSubscriptionStartDate;

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
	public function processAccountSuspendedAndNullStartDateCheck(Request $request)
	{
		try{
			$customers = Customer::whereHas('subscriptions', function(Builder $subscription) {
				$subscription->where('status', '!=', 'closed');
			})->whereNull('subscription_start_date')->orWhereNull('billing_start')->orWhereNull('billing_end')->get();

			$customerCount = $customers->count();

			if($customerCount) {
				event( new ReportNullSubscriptionStartDate( $customers->toArray() ) );
			}
		} catch (\Exception $e) {
			\Log::info($e->getMessage(). ' on the line '. $e->getLine());
		}
	}
}
