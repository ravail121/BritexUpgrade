<?php

namespace App\Http\Controllers\Api\V1\CronJobs;


use Carbon\Carbon;
use App\Model\Invoice;
use App\Model\Customer;
use Illuminate\Http\Request;
use App\Events\AutoPayReminder;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\V1\Traits\CronLogTrait;

/**
 * Class ReminderController
 *
 * @package App\Http\Controllers\Api\V1\CronJobs
 */
class ReminderController extends Controller
{
	use CronLogTrait;

	/**
	 * @param Request $request
	 */
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

        foreach ($customers as $key => $customer) {
            $invoice = Invoice::where([['customer_id', $customer->id], ['status', Invoice::INVOICESTATUS['open'] ],['type', Invoice::TYPES['monthly']]])->first();
            $request->headers->set('authorization', $customer->company->api_key);
            event(new AutoPayReminder($customer, $invoice));
	        $logEntry = [
		        'name'      => 'Auto Pay Reminder',
		        'status'    => 'success',
		        'payload'   => json_encode($customer),
		        'response'  => 'Reminded Successful for ' . $customer->id
	        ];

	        $this->logCronEntries($logEntry);
        }
    }
}