<?php

namespace App\Console;

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
        $schedule->call('App\Http\Controllers\Api\V1\CronJobs\UpdateController@checkUpdates')->daily();

	    $schedule->call('App\Http\Controllers\Api\V1\CronJobs\MonthlyInvoiceController@generateMonthlyInvoice')->dailyAt('00:02');

	    $schedule->call('App\Http\Controllers\Api\V1\CronJobs\MonthlyInvoiceController@regenerateInvoice')->dailyAt('00:04');

	    $schedule->call('App\Http\Controllers\Api\V1\CardController@autoPayInvoice')->dailyAt('00:06');

	    $schedule->call('App\Http\Controllers\Api\V1\CronJobs\ProcessController@processSubscriptions')->dailyAt('00:08');

        $schedule->call('App\Http\Controllers\Api\V1\CronJobs\ReminderController@autoPayReminder')->dailyAt('00:10');

	    $schedule->call('App\Http\Controllers\Api\V1\CronJobs\SubscriptionStatusDateController@processAccountSuspendedAndNullStartDateCheck')->dailyAt('00:12');

        $schedule->call('App\Http\Controllers\Api\V1\CronJobs\CheckInvoice@check')->dailyAt('01:00');

        $schedule->call('App\Http\Controllers\Api\V1\CronJobs\DataUsage@getUsageData')->everyTenMinutes();

	    $schedule->call('App\Http\Controllers\Api\V1\CronJobs\OrderController@order')->everyFiveMinutes()->unlessBetween('23:55', '00:15');

        $schedule->call('App\Http\Controllers\Api\V1\CronJobs\OrderDataController@order')->everyTenMinutes()->unlessBetween('23:55', '00:15');

	    $schedule->call('App\Http\Controllers\Api\V1\CronJobs\CreditCardExpirationController@cardExpirationReminder')->monthly();
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
}
