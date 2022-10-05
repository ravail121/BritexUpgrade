<?php

namespace App\Model;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class CronLog extends Model {

	protected $fillable = [
		'name',
		'status',
		'payload',
		'response'
	];

	/**
	 *
	 */
	const TYPES = [
		'check-invoice'                 => 'Check Invoice',
		'card-expiration-reminder'      => 'Card Expiration Reminder',
		'update-data-usage'             => 'Update Data Usage',
		'regenerate-invoice'            => 'Re-generate Invoice',
		'ship-order'                    => 'Ship Order',
		'update-tracking-number'        => 'Update tracking number',
		'process-subscriptions'         => 'Process Subscriptions',
		'auto-pay-reminder'             => 'Auto Pay Reminder',
		'process-account-suspended'     => 'Process Account Suspended And Null StartDate Check',
		'check-updates'                 => 'Check Updates',
		'scheduler-checker'             => 'Scheduler Checker'
	];
}