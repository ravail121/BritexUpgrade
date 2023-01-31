<?php

namespace App\Http\Controllers\Api\V1\CronJobs;

use App\CronJobRunStatus;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;

class GeneralJobController extends Controller
{
    public function generalCronJob(Request $request){
        $cronStatus = CronJobRunStatus::where('date_stamp',Carbon::today())->first();
        if($cronStatus){
            return response(['message' => 'Runned']);
        }else{
            $cronJobStatus = new CronJobRunStatus();
            $cronJobStatus->status = true;
            $cronJobStatus->date_stamp = Carbon::today();
            $cronJobStatus->save();
            
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
}
