<?php

namespace App\Http\Controllers\Api\V1\CronJobs;

use Carbon\Carbon;
use App\Model\Invoice;
use App\Model\Customer;
use Illuminate\Http\Request;
use App\Events\AutoPayReminder;
use App\Http\Controllers\Controller;

class ReminderController extends Controller
{
    public function autoPayReminder(Request $request)
    {
        $date = Carbon::today()->addDays(2);

        $customers = Customer::where([
            ['billing_end', $date], ['auto_pay', Customer::AUTO_PAY['enable']
        ]])->with('invoice', 'company')
        ->whereHas('invoice', function ($query) {
            $query->where([['status', Invoice::INVOICESTATUS['open'] ],['type', Invoice::TYPES['monthly']]]);
        })
        ->get();
        // $request = new Request;

        foreach ($customers as $key => $customer) {
            $request->headers->set('authorization', $customer->company->api_key);
            event(new AutoPayReminder($customer));
        }
    }
}