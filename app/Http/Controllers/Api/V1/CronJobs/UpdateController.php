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
use App\Http\Controllers\Controller;
use App\Http\Controllers\BaseController;

class UpdateController extends BaseController
{

    /**
     * Checks whether any table column needs updation
     * 
     * @return Response
     */
    public function checkUpdates()
    {
        $this->updateCustomerDates();
        $this->updateInvoiceStatus();
        $this->moveSubscriptionSuspendToClose();
        $this->updateProratedAmounts();
        return $this->respond(['message' => 'Updated Successfully']);
    }


    /**
     * Checks whether Today > customer.billing_end
     * 
     * @return boolean
     */
    protected function updateCustomerDates()
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
        return true;
    }


    /**
     * Checks whether Today > invoice.due_date
     *  
     * @return boolean
     */
    protected function updateInvoiceStatus()
    {
        $invoices = Invoice::where('type', 1)->where('status', 1)->get();

        foreach ($invoices as $invoice) {
            if ($invoice->today_greater_than_due_date) {
                $invoice->update([
                    'status' => 0,
                ]);

                $customer = $this->updateAccountSuspended($invoice->customer_id);
                if ($customer) {
                    $this->updateSubscriptions($customer->id);

                    event(new AccountSuspend($customer));
                }

            }
        }
        return true;
    }


    /**
     * Checks whether today-subscription.suspended_date > company.suspend_grace_period
     *  
     * @return boolean
     */
    protected function moveSubscriptionSuspendToClose()
    {
        $subscriptions = Subscription::where('status', 'suspend')->get();

        foreach ($subscriptions as $subscription) {
            $order   = Order::find($subscription->order_id);
            if ($subscription->checkGracePeriod($order->company->suspend_grace_period)) {
                $subscription->update([
                    'status' => 'closed', 
                ]);

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
    	$customer = Customer::find($customerId);
        if ($customer) {
            $customer->update([
                'account_suspended' => 1,
            ]);

        }

		return $customer;
    }


    /**
     * Updates subscription.suspend_restore_status = for-suspension
     * 
     * @param  int      $customerId
     * @return Response
     */
    private function updateSubscriptions($customerId)
    {
    	$subscriptions = Subscription::where('customer_id', $customerId)->update([
            'suspend_restore_status' => 'for-suspension',
        ]);

		return $subscriptions;
    }
}
