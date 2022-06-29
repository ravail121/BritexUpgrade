<?php

namespace App\Http\Controllers\Api\V1\CronJobs;


use App\Model\CronLog;
use App\Model\Customer;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Events\ReportNullSubscriptionStartDate;
use App\Http\Controllers\Api\V1\Traits\CronLogTrait;

/**
 * Class UpdateController
 *
 * @package App\Http\Controllers\Api\V1\CronJobs
 */
class SubscriptionStatusDateController extends Controller
{
	use CronLogTrait;
	/**
	 * @param $request
	 */
	public function processAccountSuspendedAndNullStartDateCheck(Request $request)
	{
		try{
			$customers = Customer::whereHas('subscription', function($subscription) {
				$subscription->where('status', '!=', 'closed');
			})->where(function($query){
				$query->whereNull('subscription_start_date')->orWhereNull('billing_start')->orWhereNull('billing_end');
			})->get();

			$customerCount = $customers->count();

			if($customerCount) {
				event( new ReportNullSubscriptionStartDate( $customers->toArray() ) );
			}
			$logEntry = [
				'name'      => CronLog::TYPES['process-account-suspended'],
				'status'    => 'success',
				'payload'   => json_encode($request->all()),
				'response'  => 'Processed Successfully'
			];

			$this->logCronEntries($logEntry);
		} catch (\Exception $e) {
			$logEntry = [
				'name'      => CronLog::TYPES['process-account-suspended'],
				'status'    => 'error',
				'payload'   => json_encode($request->all()),
				'response'  => $e->getMessage(). ' on the line '. $e->getLine()
			];

			$this->logCronEntries($logEntry);
			\Log::info($e->getMessage(). ' on the line '. $e->getLine());
		}
	}
}
