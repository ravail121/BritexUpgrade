<?php

namespace App\Http\Controllers\Api\V1\CronJobs;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class GeneralJobController extends Controller
{
    public function generalCronJob(Request $request){
            app('App\Http\Controllers\Api\V1\CronJobs\UpdateController')->checkUpdates($request);
        
            app('App\Http\Controllers\Api\V1\CronJobs\MonthlyInvoiceController')->generateMonthlyInvoice($request);
    
            app('App\Http\Controllers\Api\V1\CronJobs\MonthlyInvoiceController')->regenerateInvoice($request);
    
            app('App\Http\Controllers\Api\V1\CardController')->autoPayInvoice($request);
    
            app('App\Http\Controllers\Api\V1\CronJobs\ProcessController')->processSubscriptions($request);
    
            app('App\Http\Controllers\Api\V1\CronJobs\ReminderController')->autoPayReminder($request);
    
            app('App\Http\Controllers\Api\V1\CronJobs\SubscriptionStatusDateController')->processAccountSuspendedAndNullStartDateCheck($request);
    
    
            return response(['message' => 'Successfull']);
    }
}
