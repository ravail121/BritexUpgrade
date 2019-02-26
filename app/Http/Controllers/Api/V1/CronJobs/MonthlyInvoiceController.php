<?php

namespace App\Http\Controllers\Api\V1\CronJobs;

use App\Model\Tax;
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
use App\Events\MonthlyInvoice;
use App\Model\SubscriptionAddon;
use App\Model\SubscriptionCoupon;
use App\Http\Controllers\Controller;
use App\Http\Controllers\BaseController;

class MonthlyInvoiceController extends BaseController
{


    /**
     * Responses from various sources
     * @var $response
     */
    public $response;



    /**
     * Sets current date variable
     * 
     * @param Carbon $carbon
     */
    public function __construct()
    {
        $this->response = ['error' => 'Email was not sent'];
    }



    /**
     * Generates Monthly Invoice of all Customers by checking conditions
     * 
     * @return Response
     */
    public function generateMonthlyInvoice()
    {
        $customers = Customer::whereNotNull('billing_end')->get();

        foreach ($customers as $customer) {
            if ($customer->five_days_before) {  // $today->greaterThanOrEqualTo($fiveDaysBefore) is used instead of $today->lessThanOrEqualTo($fiveDaysBefore)
                if ($customer->subscription) {
                    foreach ($customer->subscription as $subscription) {
                        if ($subscription->status == 'active' || $subscription->status == 'shipping' || $subscription->status == 'for-activation') {
                            $this->response = $this->triggerEvent($customer);
                            break;
                        }
                    }
                } elseif ($customer->pending_charge) {
                    foreach ($customer->pending_charge as $pendingCharge) {
                        if ($pendingCharge->invoice_id == 0) {

                            $this->response = $this->triggerEvent($customer);
                            break;
                            
                        }
                    }
                    
                }
            }

        }
        return $this->respond($this->response);
    }


    /**
     * Sends mail through MonthlyInvoice event
     * 
     * @param  Customer   $customer
     * @return Response
     */
    protected function triggerEvent($customer)
    {
        if ($customer->invoice) {
            foreach ($customer->invoice as $invoice) {
                if ($invoice->type_not_one) {
                    $this->debitInvoiceItems($customer->id);
                    $this->response = event(new MonthlyInvoice($customer));
                    break;
                }
            }
        }
        return $this->response;
        
    }


    protected function debitInvoiceItems($customerId)
    {
        $invoiceItem = $this->addBillableSubscriptions($customerId);
        $this->subscriptionAddons($invoiceItem);
        return true;

    }



    protected function addBillableSubscriptions($customerId)
    {
        $res = '';
        $invoice = Invoice::where('customer_id', $customerId)->first();
        $subscriptions = Subscription::where('customer_id', $customerId)->whereIn('status', ['active', 'shipping', 'for-activation'])->get();

        foreach ($subscriptions as $subscription) {

            $data = [
                'invoice_id'      => $invoice->id,
                'subscription_id' => $subscription->id,
                'product_type'    => 'plan',
                'type'            => 1, // Plan Charge
                'start_date'      => $invoice->start_date,

            ];
            if ($subscription->status_shipping_or_for_activation) {
                $plan     = Plan::find($subscription->plan_id);
                $planData = $this->getPlanData($plan);

            } elseif ($subscription->status_active_not_upgrade_downgrade_status) {
                $plan     = Plan::find($subscription->plan_id);
                $planData = $this->getPlanData($plan);


            } elseif ($subscription->status_active_and_upgrade_downgrade_status) {
                $plan     = Plan::find($subscription->new_plan_id);
                $planData = $this->getPlanData($plan);

            } else {
                \Log::error('>>>>>>>>>> Subscription status not met in Monthly Invoice <<<<<<<<<<<<');
            }

            $dataForInvoiceItem = array_merge($data, $planData);
            $res = InvoiceItem::create($dataForInvoiceItem);
            \Log::info($res);

        }
        return $res;
    }


    protected function subscriptionAddons($invoiceItem)
    {
        $response = '';
        $subscriptionAddons = SubscriptionAddon::where('subscription_id', $invoiceItem->subscription_id)->get();
        foreach ($subscriptionAddons as $subscriptionAddon) {
            if ($subscriptionAddon->status != 'removal-scheduled' || $subscriptionAddon->status != 'for-removal') {
                $addon = Addon::find($subscriptionAddon->addon_id);
                $response = InvoiceItem::create([
                    'subscription_id' => $subscriptionAddon->subscription_id,
                    'product_type'    => 'addon',
                    'product_id'      => $subscriptionAddon->addon_id,
                    'type'            => 2,
                    'start_date'      => $invoiceItem->invoice->start_date,
                    'description'     => $addon->description,
                    'amount'          => $addon->amount_recurring,
                    'taxable'         => $addon->taxable,
                ]);
            }
            
        }
        return $response;
    }



    private function getPlanData($plan)
    {
        return [
            'product_id'  => $plan->id,
            'description' => $plan->description,
            'amount'      => $plan->amount_recurring,  // CONFIRM THIS FIRST
            'taxable'     => $plan->taxable,
        ];
        
    }



}
