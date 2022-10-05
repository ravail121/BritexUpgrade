<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Log as LaravelLog;

/**
 * Class Log
 * @package App\Helpers
 * @author Prajwal Shrestha
 */
class Log
{
	/**
	 * @param      $message
	 * @param null $label
	 */
	public static function info($message, $label = null)
	{
		if (!env('APP_DEBUG')) {
			return;
		}

		if ($label) {
			$label = $label . ': ';
		}

		if (is_array($message) || is_object($message)) {
			LaravelLog::info($label . print_r($message, true));
			return;
		}

		LaravelLog::info($label. $message);
	}
}