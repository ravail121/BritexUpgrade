<?php

namespace App\Console;


use Carbon\Carbon;
use App\Helpers\Log;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        //
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->call('App\Http\Controllers\Api\V1\CronJobs\UpdateController@checkUpdates')->daily()
	        ->thenPing('https://cronhub.io/ping/9e189920-dc2c-11ec-9c6a-79c61a74cb11');

	    $schedule->call('App\Http\Controllers\Api\V1\CronJobs\MonthlyInvoiceController@generateMonthlyInvoice')->dailyAt('00:02')
		    ->thenPing('https://cronhub.io/ping/9e189920-dc2c-11ec-9c6a-79c61a74cb11');

	    $schedule->call('App\Http\Controllers\Api\V1\CronJobs\MonthlyInvoiceController@regenerateInvoice')->dailyAt('00:04')
		    ->thenPing('https://cronhub.io/ping/9e189920-dc2c-11ec-9c6a-79c61a74cb11');

	    $schedule->call('App\Http\Controllers\Api\V1\CardController@autoPayInvoice')->dailyAt('00:06')
		    ->thenPing('https://cronhub.io/ping/9e189920-dc2c-11ec-9c6a-79c61a74cb11');

	    $schedule->call('App\Http\Controllers\Api\V1\CronJobs\ProcessController@processSubscriptions')->dailyAt('00:08')
		    ->thenPing('https://cronhub.io/ping/9e189920-dc2c-11ec-9c6a-79c61a74cb11');

        $schedule->call('App\Http\Controllers\Api\V1\CronJobs\ReminderController@autoPayReminder')->dailyAt('00:10')
	        ->thenPing('https://cronhub.io/ping/9e189920-dc2c-11ec-9c6a-79c61a74cb11');

	    $schedule->call('App\Http\Controllers\Api\V1\CronJobs\SubscriptionStatusDateController@processAccountSuspendedAndNullStartDateCheck')->dailyAt('00:12')
		    ->thenPing('https://cronhub.io/ping/9e189920-dc2c-11ec-9c6a-79c61a74cb11');

		$schedule->call('App\Http\Controllers\Api\V1\CronJobs\checkInvoice@check')
			->thenPing('https://cronhub.io/ping/9e189920-dc2c-11ec-9c6a-79c61a74cb11');

	    $schedule->call('App\Http\Controllers\Api\V1\CronJobs\OrderController@order')->everyFiveMinutes()->unlessBetween('23:55', '00:15')
		    ->thenPing('https://cronhub.io/ping/9e189920-dc2c-11ec-9c6a-79c61a74cb11');

        $schedule->call('App\Http\Controllers\Api\V1\CronJobs\OrderDataController@order')->everyTenMinutes()->unlessBetween('23:55', '00:15')
	        ->thenPing('https://cronhub.io/ping/9e189920-dc2c-11ec-9c6a-79c61a74cb11');

	    $schedule->call('App\Http\Controllers\Api\V1\CronJobs\CreditCardExpirationController@cardExpirationReminder')->monthly()
		    ->thenPing('https://cronhub.io/ping/9e189920-dc2c-11ec-9c6a-79c61a74cb11');
    }

    /**
     * Register the Closure based commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        require base_path('routes/console.php');
    }

	/**
	 * @param $scheduleName
	 * @param $mode
	 *
	 * @return void
	 */
	protected function logRecords($scheduleName, $mode='before') {
		$message = $mode === 'before' ? $scheduleName . ' started on ' . Carbon::now() : $scheduleName . ' completed on ' . Carbon::now();
		Log::info($message, $scheduleName);
	}
}
