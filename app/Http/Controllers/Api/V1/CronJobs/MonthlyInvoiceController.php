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
use App\libs\Constants\ConstantInterface;

class MonthlyInvoiceController extends BaseController implements ConstantInterface
{

    /**
     * Responses from various sources
     * @var $response
     */
    public $response;


    public $flag;



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
        $customers = Customer::InBillablePeriod();

        foreach ($customers as $customer) {
            if( $customer->openMonthlyInvoice ){

            } else {
                
            }

            if ($customer->subscription) {
                foreach ($customer->subscription as $subscription) {
                    if( in_array($subscription->status, ['active', 'shipping', 'for-activation']) ) {
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
                    $this->flag = '';
                    $invoice = $this->createInvoice($customer->id);

                    if ($invoice && $this->flag == '') {
                        $this->debitInvoiceItems($invoice);
                        $this->response = event(new MonthlyInvoice($customer));

                    } elseif ($invoice && $this->flag == 'pending') {
                        $this->deleteOldInvoiceItems($invoice); // This need to be changed when plan is neither upgraded nor downgraded

                        $this->debitInvoiceItems($invoice);
                        $this->response = event(new MonthlyInvoice($customer));

                    } elseif (!$invoice && $this->flag == 'error') {
                        \Log::error('Invoice not created/found.');

                    }
                    break;
                }
            }
        }
        return $this->response;
    }



    /**
     * Creates/Regenerates the Invoice
     * 
     * @param  int       $customerId
     * @return Invoice   $invoice
     */
    protected function createInvoice($customerId)
    {
        $invoice = false;
        $invoicePending = Invoice::monthlyInvoicePending()->first();
        $invoicePaid    = Invoice::monthlyInvoicePaid()->first();

        if (!$invoicePaid && !$invoicePending) {

            $customer = Customer::find($customerId);
            $data     = getInvoiceData($customer);
            $invoice  = Invoice::create($data);

        } elseif ($invoicePaid) {
            $this->flag = 'paid';

        } elseif ($invoicePending) {
            $this->flag = 'pending';
            $invoice    = $invoicePending;

        } else {
            $this->flag = 'error';
        }
        return $invoice;
    }




    protected function deleteOldInvoiceItems($invoice)
    {
        return InvoiceItem::where('invoice_id', $invoice->id)->delete();
    }





    /**
     * Sets Invoice data
     * 
     * @param  Customer  $customer
     * @return array
     */
    protected function getInvoiceData($customer)
    {
        return [
            'customer_id'             => $customer->id,
            'type'                    => self::INVOICE_TYPES['monthly'],
            'status'                  => self::STATUS['pending_payment'],
            'start_date'              => $customer->add_day_to_billing_end,
            'end_date'                => $customer->add_month_to_billing_end,
            'due_date'                => $customer->billing_end,
            'subtotal'                => $customer->credit->amount,
            'total_due'               => 2,
            'prev_balance'            => 2, 
            'payment_method'          => $customer->credit->payment_method, 
            'notes'                   => 'notes', 
            'business_name'           => $customer->company_name, 
            'billing_fname'           => $customer->fname, 
            'billing_lname'           => $customer->lname, 
            'billing_address_line_1'  => $customer->billing_address1, 
            'billing_address_line_2'  => $customer->billing_address2, 
            'billing_city'            => $customer->billing_city, 
            'billing_state'           => $customer->billing_state_id, 
            'billing_zip'             => $customer->billing_zip, 
            'shipping_fname'          => $customer->fname, 
            'shipping_lname'          => $customer->lname, 
            'shipping_address_line_1' => $customer->shipping_address1, 
            'shipping_address_line_2' => $customer->shipping_address2, 
            'shipping_city'           => $customer->shipping_city, 
            'shipping_state'          => $customer->shipping_state_id, 
            'shipping_zip'            => $customer->shipping_zip,
        ];
    }


    /**
     * Creates Invoice-items
     * 
     * @param  int       $customerId
     * @return boolean
     */
    protected function debitInvoiceItems($invoice)
    {
        $invoiceItemIds = $this->addBillableSubscriptions($invoice);
        $this->subscriptionAddons($invoiceItemIds);
        $invoiceId = $this->regulatoryFees($invoiceItemIds);
        $this->pendingCharges($invoiceId);
        return true;

    }




    /**
     * Creates invoice-items for all billable subscriptions
     * 
     * @param  int          $customerId
     * @return Response
     */
    protected function addBillableSubscriptions($invoice)
    {
        $res = [];
        $subscriptions = Subscription::where('customer_id', $invoice->customer_id)->whereIn('status', ['active', 'shipping', 'for-activation'])->get();

        foreach ($subscriptions as $subscription) {

            $data = [
                'invoice_id'      => $invoice->id,
                'subscription_id' => $subscription->id,
                'product_type'    => self::PRODUCT_TYPES['plan'],
                'description'     => 'PLAN CHARGES',
                'type'            => self::TYPES['plan_charges'],
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
            $invoiceItem = InvoiceItem::create($dataForInvoiceItem);
            array_push($res, $invoiceItem->id);
            \Log::info($res);

        }
        return $res;
    }



    /**
     * Creates Invoice-items corresponding to subscription-addons
     * 
     * @param  array       $invoiceItemIds
     * @return Response
     */
    protected function subscriptionAddons($invoiceItemIds)
    {
        $response = '';

        foreach ($invoiceItemIds as $invoiceItemId) {

            $invoiceItem        = InvoiceItem::find($invoiceItemId);
            $subscriptionAddons = SubscriptionAddon::where('subscription_id', $invoiceItem->subscription_id)->get();

            foreach ($subscriptionAddons as $subscriptionAddon) {

                if ($subscriptionAddon->status != 'removal-scheduled' || $subscriptionAddon->status != 'for-removal') {

                    $addon    = Addon::find($subscriptionAddon->addon_id);
                    $response = InvoiceItem::create([
                        'subscription_id' => $subscriptionAddon->subscription_id,
                        'product_type'    => self::PRODUCT_TYPES['addon'],
                        'product_id'      => $subscriptionAddon->addon_id,
                        'type'            => self::TYPES['feature_charges'],
                        'start_date'      => $invoiceItem->invoice->start_date,
                        'description'     => 'FEATURE CHARGES',
                        'amount'          => $addon->amount_recurring,
                        'taxable'         => $addon->taxable,
                    ]);
                }
                
            }
        }

        return $response;
    }



    /**
     * Creates Invoice-items corresponding to regulatory-fees
     * 
     * @param  array      $invoiceItemIds
     * @return Response
     */
    protected function regulatoryFees($invoiceItemIds)
    {
        $response = null;

        foreach ($invoiceItemIds as $invoiceItemId) {

            $amount = 0;

            $invoiceItem = InvoiceItem::find($invoiceItemId);
            $plan        = Plan::find($invoiceItem->subscriptionDetail->plan_id);

            if ($plan->regulatory_fee_type == 1) {
                $amount = $plan->regulatory_fee_amount;

            } elseif ($plan->regulatory_fee_type == 2) {
                $regulatoryAmount   = $plan->regulatory_fee_amount/100;
                $subscriptionAmount = $plan->amount_recurring;

                $amount = $regulatoryAmount*$subscriptionAmount;

            }

            $inserted = InvoiceItem::create([
                'subscription_id' => $invoiceItem->subscription_id,
                'product_type'    => '',
                'product_id'      => null,
                'type'            => self::TYPES['regulatory_fee'],
                'start_date'      => $invoiceItem->invoice->start_date,
                'description'     => $plan->company->regulatory_label,
                'amount'          => $amount,
                'taxable'         => self::TAX_FALSE,
            ]);

            if (!$inserted) {
                \Log::error($inserted);

            } else {
                $response = $inserted->invoice_id;

            }

        }

        return $response;
    }



    // -------- NOT FULLY DISCUSSED YET -----------
    protected function pendingCharges($invoiceId)
    {
        $pendingCharges = PendingCharge::zeroInvoice()->get();
        if ($pendingCharges) {

            $pendingCharges->update([
                'invoice_id' => $invoiceId,
            ]);

        } else {
            \Log::info('Pending Charges not found >>>>>>>>>>>>>>>>>');
            \Log::error($pendingCharges);
        }


        return $pendingCharges;
    }



    /**
     * Generates Plan data
     * 
     * @param  Plan   $plan
     * @return array
     */
    private function getPlanData($plan)
    {
        return [
            'product_id'  => $plan->id,
            'amount'      => $plan->amount_recurring,  // CONFIRM THIS FIRST
            'taxable'     => $plan->taxable,
        ];
        
    }



}
