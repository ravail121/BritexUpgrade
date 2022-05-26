<?php

namespace App\Http\Controllers\Api\V1\CronJobs;

use App\Model\Plan;
use App\Model\Order;
use App\Model\Addon;
use App\Model\Invoice;
use App\Model\Customer;
use App\Model\OrderGroup;
use App\Model\Subscription;
use Illuminate\Http\Request;
use App\Events\AccountSuspended;
use Carbon\Carbon;
use App\Http\Controllers\BaseController;
use App\Events\SubcriptionStatusChanged;
use Exception;

/**
 * Class UpdateController
 *
 * @package App\Http\Controllers\Api\V1\CronJobs
 */
class UpdateController extends MonthlyInvoiceController
{
    /**
     * Checks whether any table column needs updation
     * 
     * @return Response
     */
    public function checkUpdates(Request $request)
    {
        $this->updateCustomerDates($request);
        // $this->updateInvoiceStatus($request);
        $this->moveSubscriptionSuspendToClose($request);
        $this->updateProratedAmounts();
        $this->scheduledSupensions($request);
        $this->scheduledClosings($request);

		$logEntry = [
			'name'      => 'Check Updates',
			'status'    => 'success',
			'payload'   => $request,
			'response'  => 'Updated Successfully'
		];

		$this->logCronEntries($logEntry);
        
        return $this->respond(['message' => 'Updated Successfully']);
    }


    /**
     * Checks whether Today > customer.billing_end
     * 
     * @return boolean
     */
    protected function updateCustomerDates($request)
    {
        $customers = Customer::whereNotNull('billing_end')->get();

        foreach ($customers as $customer) {
            try {
                if ($customer->today_greater_than_billing_end) {
	                $ifMonthlyInvoice = $customer->monthlyInvoicesOfCurrentCycle->count();
	                if (!$ifMonthlyInvoice) {
		                $this->processMonthlyInvoice($customer, $request, true);
	                }
	                $customer->update([
		                'billing_start' => $customer->add_day_to_billing_end,
		                'billing_end'   => $customer->add_month_to_billing_end_for_invoice,
	                ]);
                }
            } catch (Exception $e) {
                \Log::info($e->getMessage(). ', From: UpdateController@updateCustomerDates: '.$e->getLine().', Possible issue from customer with id of '.$customer->id);
            }
        }
        return true;
    }


    /**
     * Checks whether Today > invoice.due_date
     *  
     * @return boolean
     */
    protected function updateInvoiceStatus($request)
    {
        $invoices = Invoice::where('type', 1)->where('status', 1)->get();

        foreach ($invoices as $invoice) {
            try {
                if ($invoice->today_greater_than_due_date) {
                    $invoice->update([
                        'status' => 0,
                    ]);

                    $customer = $this->updateAccountSuspended($invoice->customer_id);
                    if ($customer) {
                        $subscriptions = $this->updateSubscriptions($customer->id);
                        $request->headers->set('authorization', $customer->company->api_key);
                        event(new AccountSuspended(Customer::find($customer->id), $subscriptions, $invoice->subtotal));
                    }

                }
            } catch (Exception $e) {
                \Log::info($e->getMessage(). ', From: UpdateController@updateInvoiceStatus: '.$e->getLine().', Possible issue from invoice with id of '.$invoice->id);
            }
        }
        return true;
    }


    /**
     * Checks whether today-subscription.suspended_date > company.suspend_grace_period
     *  
     * @return boolean
     */
    protected function moveSubscriptionSuspendToClose($request)
    {
        $subscriptions = Subscription::where('status', Subscription::STATUS['suspended'])->get();

        foreach ($subscriptions as $subscription) {
            try {
                $order   = Order::find($subscription->order_id);
                if ($subscription->checkGracePeriod($order->company->suspend_grace_period)) {
                    $subscription->update([
                        'status'                => Subscription::STATUS['closed'],
                        'sub_status'            => Subscription::SUB_STATUSES['confirm-closing'],
                        'scheduled_close_date'  => null,
                        'closed_date'           => Carbon::today()
                    ]);
                }
                $request->headers->set('authorization', $subscription->customerRelation->company->api_key);
                event(new SubcriptionStatusChanged($subscription->id));
            } catch (Exception $e) {
                \Log::info($e->getMessage(). ' on the line '.$e->getLine().', Possible issue from subscription with id of '.$subscription->id);
            }
        }
        return true;
    }


    /**
     * Updates the Prorated Amounts of Plan and Addon
     * 
     * @return boolean
     */
    protected function updateProratedAmounts()
    {
        $orders = Order::where('status', 0)->get();

        foreach ($orders as $order) {
            try {
                $orderGroup = OrderGroup::find($order->id);
                if ($orderGroup) {

                    $plan = Plan::find($orderGroup->plan_id);
                    if ($plan) {
                        $orderGroup->update([
                            'plan_prorated_amt' => $plan->amount_recurring,
                        ]);
                    }

                    foreach ($orderGroup->order_group_addon as $orderGroupAddon) {
                        $addon = Addon::find($orderGroupAddon->addon_id);
                        if ($addon) {
                            $orderGroupAddon->update([
                                'prorated_amt' => $addon->amount_recurring,
                            ]);

                        }
                    }
                    
                }
            } catch (Exception $e) {
                \Log::info($e->getMessage(). ' on the line '.$e->getLine().', Possible issue from order with id of '.$order->id);
            }
        }
        return true;
    }


    /**
     * Updates the customer.account_suspended = 1
     * 
     * @param  int       $customerId
     * @return Response
     */
    private function updateAccountSuspended($customerId)
    {
        try {
            $customer = Customer::find($customerId);
            if ($customer) {
                $customer->update([
                    'account_suspended' => 1,
                ]);

            }

            return $customer;
        } catch (Exception $e) {
            \Log::info($e->getMessage(). ' on the line '.$e->getLine().', Possible issue from customer with id of '.$customer->id);
        }
    }


    /**
     * Updates subscription.sub_status = for-suspension
     * 
     * @param  int      $customerId
     * @return Response
     */
    private function updateSubscriptions($customerId)
    {
        $subscriptions = Subscription::where('customer_id', $customerId)->with('plan', 'subscriptionAddonNotRemoved')->get();

        foreach($subscriptions as $subscription){
            try {
                $subscription->update([
                    'sub_status' => 'for-suspension',
                ]);
            } catch (Exception $e) {
                \Log::info($e->getMessage(). ' on the line '.$e->getLine().', Possible issue from subscription with id of '.$subscription->id);
            }
        }
        
		return $subscriptions;
    }

	/**
	 * @param $request
	 */
	protected function scheduledSupensions($request)
    {
        $scheduledSuspensions = Subscription::where('scheduled_suspend_date', '<=' ,Carbon::today())->get();
        foreach ($scheduledSuspensions as $sub) {
            try {
                $sub->update([
                    'status'                => Subscription::STATUS['suspended'],
                    'sub_status'            => Subscription::SUB_STATUSES['confirm-suspension'],
                    'scheduled_suspend_date'=> null,
                    'suspended_date'        => Carbon::today()
                ]);
                $request->headers->set('authorization', $sub->customerRelation->company->api_key);
                
                event(new SubcriptionStatusChanged($sub->id));
            } catch (Exception $e) {
                \Log::info($e->getMessage(). ' on the line '.$e->getLine().', Possible issue from subscription with id of '.$sub->id);
            }
        }
    }

	/**
	 * @param $request
	 */
	protected function  scheduledClosings($request)
    {
        $scheduledClosings = Subscription::where('scheduled_close_date', '<=', Carbon::today())->get();
        foreach ($scheduledClosings as $sub) {
            try {
                $sub->update([
                    'status'                => Subscription::STATUS['closed'],
                    'sub_status'            => Subscription::SUB_STATUSES['confirm-closing'],
                    'scheduled_close_date'  => null,
                    'closed_date'           => Carbon::today()
                ]);
                $request->headers->set('authorization', $sub->customerRelation->company->api_key);
                event(new SubcriptionStatusChanged($sub->id));
            } catch (Exception $e) {
                \Log::info($e->getMessage(). ' on the line '.$e->getLine().', Possible issue from subscription with id of '.$sub->id);
            }
        }
    }

}
