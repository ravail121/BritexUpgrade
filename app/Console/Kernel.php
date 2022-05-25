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
        ->before(function() {
			$this->logRecords('Check Updates');
        })->after(function() {
	        $this->logRecords('Check Updates', 'after');
        });

	    $schedule->call('App\Http\Controllers\Api\V1\CronJobs\MonthlyInvoiceController@generateMonthlyInvoice')->dailyAt('00:02')->before(function() {
		    $this->logRecords('Generate Monthly Invoice');
	    })->after(function() {
		    $this->logRecords('Generate Monthly Invoice', 'after');
	    });;

	    $schedule->call('App\Http\Controllers\Api\V1\CronJobs\MonthlyInvoiceController@regenerateInvoice')->dailyAt('00:04')->before(function() {
		    $this->logRecords('Generate Invoice');
	    })->after(function() {
		    $this->logRecords('Generate Invoice', 'after');
	    });;

	    $schedule->call('App\Http\Controllers\Api\V1\CardController@autoPayInvoice')->dailyAt('00:06')->before(function() {
		    $this->logRecords('Auto Pay Invoice');
	    })->after(function() {
		    $this->logRecords('Auto Pay Invoice', 'after');
	    });;

	    $schedule->call('App\Http\Controllers\Api\V1\CronJobs\ProcessController@processSubscriptions')->dailyAt('00:08')->before(function() {
		    $this->logRecords('Process Subscriptions');
	    })->after(function() {
		    $this->logRecords('Process Subscriptions', 'after');
	    });;

        $schedule->call('App\Http\Controllers\Api\V1\CronJobs\ReminderController@autoPayReminder')->dailyAt('00:10')->before(function() {
	        $this->logRecords('Auto Pay Reminder');
        })->after(function() {
	        $this->logRecords('Auto Pay Reminder', 'after');
        });;

	    $schedule->call('App\Http\Controllers\Api\V1\CronJobs\SubscriptionStatusDateController@processAccountSuspendedAndNullStartDateCheck')->dailyAt('00:12')->before(function() {
		    $this->logRecords('Suspended And Null Start Date Check');
	    })->after(function() {
		    $this->logRecords('Suspended And Null Start Date Check', 'after');
	    });;

		$schedule->call('App\Http\Controllers\Api\V1\CronJobs\checkInvoice@check')->dailyAt('01:00')->before(function() {
			$this->logRecords('Check Invoice');
		})->after(function() {
			$this->logRecords('Check Invoice', 'after');
		});;

	    $schedule->call('App\Http\Controllers\Api\V1\CronJobs\OrderController@order')->everyFiveMinutes()->unlessBetween('23:55', '00:15')->before(function() {
		    $this->logRecords('Ship Order');
	    })->after(function() {
		    $this->logRecords('Ship Order', 'after');
	    });;

        $schedule->call('App\Http\Controllers\Api\V1\CronJobs\OrderDataController@order')->everyTenMinutes()->unlessBetween('23:55', '00:15')->before(function() {
	        $this->logRecords('Update Tracking Number');
        })->after(function() {
	        $this->logRecords('Update Tracking Number', 'after');
        });;

	    $schedule->call('App\Http\Controllers\Api\V1\CronJobs\CreditCardExpirationController@cardExpirationReminder')->monthly()->before(function() {
		    $this->logRecords('Card Expiration Reminder');
	    })->after(function() {
		    $this->logRecords('Card Expiration Reminder', 'after');
	    });;
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
