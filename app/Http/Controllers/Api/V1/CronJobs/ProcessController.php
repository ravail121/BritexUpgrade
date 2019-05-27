<?php

namespace App\Http\Controllers\Api\V1\CronJobs;

use Carbon\Carbon;
use App\Model\Invoice;
use App\Model\Subscription;
use Illuminate\Http\Request;
use App\Events\AccountSuspended;
use App\Model\SubscriptionAddon;
use App\Http\Controllers\Controller;
use App\Http\Controllers\BaseController;

class ProcessController extends BaseController
{
    public function processSubscriptions()
    {
    	$this->processSuspensions();
    	$this->processDowngrades();
        $this->processAddonRemovals();

    	return $this->respond(['message' => 'Processed Successfully']);
    }

    public function processSuspensions()
    {
        $pendingMonthlyInvoices = Invoice::monthly()->pendingPayment()->overDue()->get();

        foreach($pendingMonthlyInvoices as $pendingMonthlyInvoice){
            $customer = $pendingMonthlyInvoice->customer;
            
            $pendingMonthlyInvoice->update(['status' => Invoice::STATUS['closed_and_unpaid']]);

            $customer->update(['account_suspended' => true]);

            foreach($customer->subscription as $subscription){
                $subscription->update([
                    'sub_status'            => Subscription::SUB_STATUSES['account-past-due'],
                    'account_past_due_date' => Carbon::today()
                ]);
            }

            event(new AccountSuspended($customer));
        }
    }


    protected function processDowngrades()
    {
    	$subscriptions = Subscription::todayEqualsDowngradeDate()->get();

    	foreach ($subscriptions as $subscription) {
    		$subscription->update([
	    		'upgrade_downgrade_status' => 'for-downgrade',
	    		'old_plan_id'    		   => $subscription->plan_id,
	    		'plan_id'        		   => $subscription->new_plan_id,
	    		'new_plan_id'    		   => null,
	    		'downgrade_date' 		   => null,
    		]);

    		// Need to add row in 'subscription_log' with category="downgrade-" => NOT CLEARED
    	}
    	return true;
    }

    protected function processAddonRemovals()
    {
        /*
        SubscriptionAddon::todayEqualsRemovalDate()->update([
            'status' => 'for-removal',
    		'removal_date' => null,
            // ToDo: may be we should set `subscription_addon.removal_date = null`?
            // Asked client about this.
        ]);
        */
        //Above logic is not working
        SubscriptionAddon::where('removal_date', Carbon::today())->update([
            'status' => 'for-removal',
            'removal_date' => null
        ]);

    	return true;
    }
}
