<?php

namespace App\Http\Controllers\Api\V1\Traits;

use App\Model\CronLog;

/**
 * Trait CronLogTrait
 *
 * @package App\Http\Controllers\Api\V1\Traits
 */
trait CronLogTrait {

	/**
	 * @param $entry
	 *
	 * @return void
	 */
	public function logCronEntries($entry)
	{
		CronLog::create($entry);
	}

}