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
        $schedule->call('App\Http\Controllers\Api\V1\CronJobs\MonthlyInvoiceController@generateMonthlyInvoice')->daily();

        $schedule->call('App\Http\Controllers\Api\V1\CronJobs\UpdateController@checkUpdates')->daily();

        $schedule->call('App\Http\Controllers\Api\V1\CronJobs\ReminderController@autoPayReminder')->daily();

        $schedule->call('App\Http\Controllers\Api\V1\CronJobs\ProcessController@processSubscriptions')->daily();

        $schedule->call('App\Http\Controllers\Api\V1\CronJobs\RegenerateInvoiceController@regenerateInvoice')->daily();

        // $schedule->call('App\Http\Controllers\Api\V1\CardController@autoPayInvoice')->daily();
        
        //$schedule->call('App\Http\Controllers\Api\V1\CronJobs\OrderController@order')->everyMinute();
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
