<?php

namespace App\Http\Controllers\Api\V1\CronJobs;

use Carbon\Carbon;
use App\Model\CronLog;
use Illuminate\Http\Request;
use App\Events\ReportSchedulerStatus;
use App\Http\Controllers\BaseController;
use App\Http\Controllers\Api\V1\Traits\CronLogTrait;

/**
 * Class ProcessController
 *
 * @package App\Http\Controllers\Api\V1\CronJobs
 */
class SchedulerChecker extends BaseController
{
	use CronLogTrait;
	/**
	 * @param Request $request
	 *
	 * @return \Illuminate\Http\JsonResponse
	 */

	public function check()
	{
		$cronEntries = [];
		try {

			$cronLogTypes = CronLog::TYPES;

			foreach($cronLogTypes as $cronName) {
				$cronEntries[$cronName] = CronLog::whereDate('created_at', Carbon::today())->where('name', $cronName)->exists();

			}
			event( new ReportSchedulerStatus( $cronEntries ) );

			$logEntry = [
				'name'      => CronLog::TYPES['scheduler-checker'],
				'status'    => 'success',
				'payload'   => json_encode($cronEntries),
				'response'  => 'Scheduler Checker ran successfully'
			];

			$this->logCronEntries($logEntry);
		} catch (\Exception $e) {
			$logEntry = [
				'name'      => CronLog::TYPES['scheduler-checker'],
				'status'    => 'error',
				'payload'   => '',
				'response'  => $e->getMessage()
			];

			$this->logCronEntries($logEntry);
		}

	}
}
