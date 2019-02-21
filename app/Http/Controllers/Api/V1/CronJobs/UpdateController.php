<?php

namespace App\Http\Controllers\Api\V1\CronJobs;

use Validator;
use App\Model\Tax;
use App\Model\Sim;
use App\Model\Plan;
use App\Model\Order;
use App\Model\Addon;
use App\Model\Device;
use App\Model\Coupon;
use App\Model\Company;
use App\Model\Invoice;
use App\Model\Customer;
use App\Model\InvoiceItem;
use App\Model\Subscription;
use Illuminate\Http\Request;
use App\Model\PendingCharge;
use App\Model\CustomerCoupon;
use App\Events\MonthlyInvoice;
use App\Model\SubscriptionAddon;
use App\Model\SubscriptionCoupon;
use App\Model\BusinessVerification;
use App\Model\CustomerStandaloneSim;
use App\Http\Controllers\Controller;
use App\Model\CustomerStandaloneDevice;
use App\Http\Controllers\BaseController;

class UpdateController extends BaseController
{


    public function updateCustomerDates()
    {
        $customers = Customer::whereNotNull('billing_end')->get();

    	foreach ($customers as $customer) {
    		if ($customer->today_greater_than_billing_end) {
    			$customer->update([
    				'billing_start' => $customer->add_day_to_billing_end,
    				'billing_end'   => $customer->add_month_to_billing_end,
    			]);
    		}
    	}
    	return $this->respond(['message' => 'Updated Successfully']);
    }



    public function updateInvoiceStatus()
    {
    	$invoices = Invoice::where('type', 1)->where('status', 1)->get();

    	foreach ($invoices as $invoice) {
    		if ($invoice->today_greater_than_due_date) {
    			$invoice->update([
    				'status' => 0,
    			]);

    			$this->updateAccountSuspended($invoice->customer_id);
    			$this->updateSubscriptions($invoice->customer_id);

    		}
    	}
    	return $this->respond(['message' => 'Updated Successfully']);
    }


    protected function updateAccountSuspended($customerId)
    {
    	$customer = Customer::find($customerId);
    	$customer->update([
			'account_susepended' => 1,
		]);
		return $customer;
    }


    protected function updateSubscriptions($customerId)
    {
    	$subscriptions = Subscription::where('customer_id', $customerId)->update([
            'suspend_restore_status' => 'for-suspension',
        ]);

		return $subscriptions;
    }
}
