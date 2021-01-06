<?php

namespace App\Http\Controllers\Api\V1\CronJobs;

use Carbon\Carbon;
use App\Model\Invoice;
use App\Model\CustomerLog;
use App\Model\Subscription;
use Illuminate\Http\Request;
use App\Events\AccountSuspended;
use App\Model\SubscriptionAddon;
use App\Http\Controllers\BaseController;

/**
 * Class ProcessController
 *
 * @package App\Http\Controllers\Api\V1\CronJobs
 */
class ProcessController extends BaseController
{
	/**
	 * @param Request $request
	 *
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function processSubscriptions(Request $request)
    {
    	$this->processSuspensions($request);
        $this->processDowngrades();
        $this->processAddonRemovals();

    	return $this->respond(['message' => 'Processed Successfully']);
    }

	/**
	 * @param $request
	 */
	public function processSuspensions($request)
    {
        $pendingMonthlyInvoices = Invoice::monthly()->pendingPayment()->overDue()->with('customer')->get();

        foreach($pendingMonthlyInvoices as $pendingMonthlyInvoice){
            try {
                $customer = $pendingMonthlyInvoice->customer;
                
                $pendingMonthlyInvoice->update(['status' => Invoice::STATUS['closed_and_unpaid']]);

                $customer->update(['account_suspended' => true]);

                CustomerLog::create( array("customer_id"=>$customer->id, "content"=> "Account Suspended" ) );

                $subscriptions = $customer->nonClosedSubscriptions->load('plan', 'subscriptionAddonNotRemoved');

                foreach($subscriptions as $subscription){
                    $subscription->update([
                        'sub_status'            => Subscription::SUB_STATUSES['account-past-due'],
                        'account_past_due_date' => Carbon::today()
                    ]);   
                }
                $request->headers->set('authorization', $customer->company->api_key);
                event(new AccountSuspended($customer, $subscriptions, $pendingMonthlyInvoice->subtotal));
            } catch (Exception $e) {
                \Log::info($e->getMessage(). ' on the line '.$e->getLine().', Possible issue from invoice with id of '.$pendingMonthlyInvoice->id);
            }
        }
    }


	/**
	 * @return bool
	 */
	protected function processDowngrades()
    {
    	$subscriptions = Subscription::todayEqualsDowngradeDate()->get();
        
    	foreach ($subscriptions as $subscription) {
            try {
                if ($subscription->new_plan_id) {
                    $subscription->update([
                        'upgrade_downgrade_status' => 'for-downgrade',
                        'old_plan_id'    		   => $subscription->plan_id,
                        'plan_id'        		   => $subscription->new_plan_id,
                        'new_plan_id'    		   => null,
                        'downgrade_date' 		   => null,
                    ]);
        
                }
                // Need to add row in 'subscription_log' with category="downgrade-" => NOT CLEARED
            } catch (Exception $e) {
                \Log::info($e->getMessage(). ' on the line '.$e->getLine().', Possible issue from subscription with id of '.$subscription->id);
            }
    	}
    	return true;
    }

	/**
	 * @return bool
	 */
	protected function processAddonRemovals()
    {
        SubscriptionAddon::where('removal_date', Carbon::today())->update([
            'status'        => 'for-removal',
            'removal_date'  => null
        ]);

    	return true;
    }
}
