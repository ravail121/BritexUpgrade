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
use App\Model\CouponProduct;
use App\Model\CustomerCoupon;
use App\Events\MonthlyInvoice;
use App\Model\SubscriptionAddon;
use App\Model\CouponProductType;
use App\Model\SubscriptionCoupon;
use App\Http\Controllers\Controller;
use App\Http\Controllers\BaseController;
use App\libs\Constants\ConstantInterface;
use Carbon\Carbon;

class MonthlyInvoiceController extends BaseController implements ConstantInterface
{
    use InvoiceTrait;

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

        // $customers = Customer::shouldBeGeneratedNewInvoices();
        $customers = Customer::shouldBeGeneratedNewInvoices();
        
        //\Log::info($customers);
        foreach ($customers as $customer) {
            /*if( $customer->openMonthlyInvoice ){


            } else {
                
            }*/
            
            if ($customer->billableSubscriptions->count()) {
                // ToDo: Temporary fix. Check why 'billing_state'
                // is null
                // And will there be a guarranty that every state
                // has tax?
                
                if(!$customer->stateTax){
                    \Log::info("----State Tax not present for customer with id {$customer->id} and stateTax {$customer->stateTax}. Monthly Invoice Generation skipped");
                    continue;
                }

                $invoice = Invoice::create($this->getInvoiceData($customer));

                $billableSubscriptionInvoiceItems = $this->addBillableSubscriptions($customer->billableSubscriptions, $invoice);

                $billableSubscriptionAddons = $this->addSubscriptionAddons($billableSubscriptionInvoiceItems);

                $regulatoryFees = $this->regulatoryFees($billableSubscriptionInvoiceItems);

                $pendingCharges = $this->pendingCharges($invoice);
                $totalPendingCharges = $pendingCharges->sum('amount') ? $pendingCharges->sum('amount') : 0;

                $taxes = $this->addTaxes($customer, $invoice, $billableSubscriptionInvoiceItems);
                
                // Subtotal = sum of ( Debits - coupons [applied to each subscription] + Taxes [applied to each subscription] )

                // Add Coupons
                $couponAccount = $this->customerAccountCoupons($customer, $invoice);
                $couponSubscription = $this->customerSubscriptionCoupons($invoice, $customer->billableSubscriptions);
                
                $couponDiscountTotal = $invoice->invoiceItem->whereIn('type', 
                    [
                        InvoiceItem::TYPES['coupon'],
                        InvoiceItem::TYPES['manual']
                    ])->sum('amount');

                $monthlyCharges = $invoice->invoiceItem->whereIn('type', 
                    [
                        InvoiceItem::TYPES['plan_charges'],
                        InvoiceItem::TYPES['feature_charges'],
                        InvoiceItem::TYPES['regulatory_fee'],
                        InvoiceItem::TYPES['taxes'],
                    ])->sum('amount');

                //Plan charge + addon charge + pending charges + taxes - discount = monthly charges
                
                $subtotal = $monthlyCharges + $totalPendingCharges - $couponDiscountTotal;

                $invoiceUpdate = $invoice->update(compact('subtotal'));

                $totalDue = $this->applyCredits($customer, $invoice);

                $invoice->update(['total_due' => $totalDue]);
    
                $order = $this->insertOrder($invoice);

                /*foreach ($customer->billableSubscriptions as $billableSubscription) {
                    $this->response = $this->triggerEvent($customer);
                    break;
                }*/
            } /*elseif ($customer->pending_charge) {
                foreach ($customer->pending_charge as $pendingCharge) {
                    if ($pendingCharge->invoice_id == 0) {

                        $this->response = $this->triggerEvent($customer);
                        break;
                        
                    }
                }
            }*/


        }

        return $this->respond($this->response);
    }

    public function applyCredits($customer, $invoice)
    {
        $totalDue = $invoice->subtotal;

        foreach ($customer->creditsNotAppliedCompletely as $creditNotAppliedCompletely) {
            
            $pendingCredits = $creditNotAppliedCompletely->amount;
            
            if($totalDue >= $pendingCredits){
                $creditNotAppliedCompletely->update(['applied_to_invoice' => 1]);
                
                $creditNotAppliedCompletely->usedOnInvoices()->create([
                    'invoice_id'  => $invoice->id,
                    'amount'      => $pendingCredits,
                    'description' => "{$pendingCredits} applied on invoice id {$invoice->id}",
                ]);

                $totalDue -= $pendingCredits;

                if($totalDue == $pendingCredits) break;
            }else {
                $creditNotAppliedCompletely->usedOnInvoices()->create([
                    'invoice_id'  => $invoice->id,
                    'amount'      => $totalDue,
                    'description' => "{$totalDue} applied on invoice id {$invoice->id}",
                ]);
                // Warning: Don't move it above as the value
                $totalDue -= $totalDue;
                break;
            }
        }

        return $totalDue;
    }

    public function addTaxes($customer, $invoice, $billableSubscriptionInvoiceItems)
    {
        $taxes = collect();

        $taxPercentage = ($customer->stateTax->rate)/100;
        
        $taxableBillableSubscriptionInvoiceItems = $billableSubscriptionInvoiceItems->where('taxable', true)->all();

        // For each plan/subscription
        // Calculate total of plan + feature, one-time or usage charges
        // and apply tax on it
        foreach($taxableBillableSubscriptionInvoiceItems as $taxableBillableSubscriptionInvoiceItem){

            $amount = ($taxableBillableSubscriptionInvoiceItem
                            ->subscriptionDetail
                            ->invoiceItemOfTaxableServices
                            ->where('invoice_id', $invoice->id)
                            ->pluck('amount')->sum()
                       ) * $taxPercentage;

            $data = [
                'subscription_id' => $taxableBillableSubscriptionInvoiceItem->subscription_id,
                'product_type'    => '',
                'product_id'      => null,
                'type'            => InvoiceItem::INVOICE_ITEM_TYPES['taxes'],
                'start_date'      => $invoice->start_date,
                'description'     => '(Taxes)',
                'amount'          => $amount,
                'taxable'         => self::TAX_FALSE
            ];

            $taxes->push(
                $invoice->invoiceItem()->create($data)
            );

        }

        return $taxes;

    }

    public function customerAccountCoupons($customer, $invoice)
    {
        $customerCouponRedeemable = $customer->customerCouponRedeemable;

        foreach ($customerCouponRedeemable as $customerCoupon) {
            $coupon = $customerCoupon->coupon;

            if($customerCoupon->cycles_remaining < 1) continue;

            list($isApplicable, $subscriptions) = 
                        $this->isCustomerAccountCouponApplicable(
                            $coupon,
                            $customer->billableSubscriptions
                        );
            
            if($isApplicable){
                $coupon->load('couponProductTypes', 'couponProducts');

                foreach($subscriptions as $subscription){

                    $amount = $this->customerAccountCouponAmount($subscription, $coupon);

                    // Possibility of returning 0 as well but
                    // returns false when coupon is not applicable
                    if($amount === false) continue;

                    $invoice->invoiceItem()->create([
                        'subscription_id' => $subscription->id,
                        'product_type'    => '',
                        'product_id'      => $coupon->id,
                        'type'            => InvoiceItem::TYPES['coupon'],
                        'description'     => "(Customer Account Coupon) $coupon->code",
                        'amount'          => $amount,
                        'start_date'      => $invoice->start_date,
                        'taxable'         => self::TAX_FALSE,
                    ]);
                }

                $customerCoupon->update(['cycles_remaining' => $customerCoupon['cycles_remaining'] - 1]);

                // ToDo: Add logs,Order not provided in requirements
            }

        }
    }

    private function isCustomerAccountCouponApplicable($coupon, $subscriptions)
    {
        $isApplicable  = true;
        
        if($multilineMin = $coupon->multiline_min){
            // if coupon.multiline_restrict_plans == true means
            // only specific subscription are considered for
            // coupon.multiline_min criteria
            if($coupon->multiline_restrict_plans){
                $supportedPlanTypes = $coupon->multilinePlanTypes->pluck('plan_type');

                $subscriptions = $subscriptions->filter(function($subscription, $key) use ($supportedPlanTypes){
                    return $supportedPlanTypes->contains($subscription->plan->type);
                });
            }

            $isApplicable = $isApplicable && ($subscriptions->count() >= $multilineMin);

        }

        // ToDO:
        // 1. What if user has more subscriptions?
        // 2. Does `multiline_restrict_plans == 1` affects it?
        if($coupon->multiline_max){
            $isApplicable = $isApplicable && $subscriptions->count() <= $coupon->multiline_max;
        }
        
        return [$isApplicable, $subscriptions];
    }

    private function customerAccountCouponAmount($subscription, $coupon)
    {
        $plan = $subscription->plan;

        // Had to use continue, so used if else 
        // and not switch statement
        if($coupon->class == Coupon::CLASSES['APPLIES_TO_SPECIFIC_TYPES']){
            $couponProductTypeForPlans = $coupon->couponProductTypes->where('type', CouponProductType::TYPES['plan']);
            $couponProductTypeForThisPlan = $couponProductTypeForPlans->where('sub_type', $plan->type)->first();
            
            // Coupon doesnot support plans or 
            // doesnot supports this plan
            if(!$couponProductTypeForPlans || !$couponProductTypeForThisPlan ) return false;

            $amount = $couponProductTypeForThisPlan->amount;

        } elseif($coupon->class == Coupon::CLASSES['APPLIES_TO_SPECIFIC_PRODUCT']){
            $couponProductsForPlans = $coupon->couponProducts->where('product_type', CouponProduct::PRODUCT_TYPES['plan']);
            $couponProductForThisPlan = $couponProductsForPlans->where('product_id', $plan->id)->first();

            // Coupon doesnot support plans or 
            // doesnot supports this plan
            if(!$couponProductsForPlans || !$couponProductForThisPlan ) return false;

            $amount = $couponProductForThisPlan->amount;
        } else {
            $amount = $coupon->amount;
        }

        if($coupon->fixed_or_perc == Coupon::FIXED_PERC_TYPES['percentage']){
            $amount = $plan->amount_recurring * ($amount/100);
        }

        return $amount;
    }


    public function customerSubscriptionCoupons($invoice, $subscriptions)
    {
        foreach($subscriptions as $subscription){
            
            $subscriptionCouponRedeemable = $subscription->subscriptionCouponRedeemable;

            // Subscription doesnot has any coupons
            if(!$subscriptionCouponRedeemable) continue;

            foreach ($subscriptionCouponRedeemable as $subscriptionCoupon) {
                $coupon = $subscriptionCoupon->coupon;

                if($subscriptionCoupon->cycles_remaining < 1) continue;

                $coupon->load('couponProductTypes', 'couponProducts');

                $amount = $this->customerAccountCouponAmount($subscription, $coupon);

                // Possibility of returning 0 as well but
                // returns false when coupon is not applicable
                if($amount === false) continue;

                $invoice->invoiceItem()->create([
                    'subscription_id' => $subscription->id,
                    'product_type'    => '',
                    'product_id'      => null,
                    'type'            => InvoiceItem::TYPES['coupon'],
                    'description'     => "(Subscription Coupon) $coupon->code",
                    'amount'          => $amount,
                    'start_date'      => $invoice->start_date,
                    'taxable'         => self::TAX_FALSE,
                ]);

                $subscriptionCoupon->update(['cycles_remaining' => $subscriptionCoupon['cycles_remaining'] - 1]);
            }

        }

    }

    protected function insertOrder($invoice)
    {
        $hash = md5(time());
    
        if ($invoice->type === Invoice::TYPES['monthly']) {
            Order::create([
                'status'        => 1,
                'invoice_id'    => $invoice->id,
                'hash'          => $hash,
                'company_id'    => Company::Id['britex'],
                'customer_id'   => $invoice->customer_id,
            ]);
        }

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
        $invoicePending     = Invoice::monthlyInvoicePending()->first();
        $invoicePaid        = Invoice::monthlyInvoicePaid()->first();
        $regenratedInvoice  = $this->regenerateInvoice($customerId);

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
            // Todo: add notes
            'subtotal'                => 0,
            'total_due'               => 0,
            'prev_balance'            => 0, 
            'payment_method'          => 'USAePay', 
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
        $this->addSubscriptionAddons($invoiceItemIds);
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
    protected function addBillableSubscriptions($billableSubscriptions, $invoice)
    {
        $invoiceItems = collect();
        
        foreach ($billableSubscriptions as $billableSubscription) {
            $data = [
                'subscription_id' => $billableSubscription->id,
                'product_type'    => InvoiceItem::INVOICE_ITEM_PRODUCT_TYPES['plan'],
                'type'            => InvoiceItem::INVOICE_ITEM_TYPES['plan_charges'],
                'start_date'      => $invoice->start_date,
                'description'     => "(Billable Subscription) {$billableSubscription->plan->description}",
                // Values added below
                'product_id'      => '',
                'amount'          => '',
                'taxable'         => '',
            ];

            if ($billableSubscription->is_status_shipping_or_for_activation) {
                $plan     = $billableSubscription->plan;
                $planData = $this->getPlanData($plan);

            }
            // Subscription that are scheduled for Suspension/Closure  are already excluded
            // (sub_status!=’suspend-scheduled’ AND sub_status!=’close-scheduled’)
            // See  in Customer@billableSubscriptions() and
            // Subscription@scopeBillabe()
            elseif ($billableSubscription->is_status_active_not_upgrade_downgrade_status) {
                $plan     = $billableSubscription->plan;
                $planData = $this->getPlanData($plan);

            } elseif ($billableSubscription->status_active_and_upgrade_downgrade_status) {
                $plan     = $billableSubscription->newPlanDetail;
                $planData = $this->getPlanData($plan);

            } else {
                \Log::error(">>>>>>>>>> Subscription status not met in Monthly Invoice for subscription.id = {$billableSubscription} <<<<<<<<<<<<");
                continue;
            }

            $dataForInvoiceItem = array_merge($data, $planData);
            
            $invoiceItems->push(
                $invoice->invoiceItem()->create($dataForInvoiceItem)
            );
        }
        
        return $invoiceItems;
    }



    /**
     * Creates Invoice-items corresponding to subscription-addons
     * 
     * @param  array       $invoiceItemIds
     * @return Response
     */
    protected function addSubscriptionAddons($subscriptionInvoiceItems)
    {
        $subscriptionAddons = collect();

        foreach ($subscriptionInvoiceItems as $invoiceItem) {
            $subscription = $invoiceItem->subscriptionDetail;

            foreach ( $subscription->billableSubscriptionAddons as $subscriptionAddon ) {

                $addon = $subscriptionAddon->addon;
                
                $subscriptionAddons->push(
                    $subscription->invoiceItemDetail()->create([
                        'invoice_id'   => $invoiceItem->invoice_id,
                        'product_type' => InvoiceItem::INVOICE_ITEM_PRODUCT_TYPES['addon'],
                        'product_id'   => $subscriptionAddon->addon_id,
                        'type'         => InvoiceItem::INVOICE_ITEM_TYPES['feature_charges'],
                        'start_date'   => $invoiceItem->invoice->start_date,
                        'description'  => "(Billable Addon) {$addon->description}",
                        'amount'       => $addon->amount_recurring,
                        'taxable'      => $addon->taxable,
                    ])
                );
            }
        }

        return $subscriptionAddons;
    }



    /**
     * Creates Invoice-items corresponding to regulatory-fees
     * 
     * @param  array      $invoiceItemIds
     * @return Response
     */
    protected function regulatoryFees($subscriptionInvoiceItems)
    {
        $regulatoryFees = collect();

        foreach ($subscriptionInvoiceItems as $invoiceItem) {
            // ToDO: Can there be a case of new_plan_id?
            // I don't see it implemented
            
            $regulatoryFee = $this->addRegulatorFeesToSubscription(
                $invoiceItem->subscriptionDetail,
                $invoiceItem->invoice,
                self::TAX_FALSE
            );

            $regulatoryFees->push( $regulatoryFee );
        }

        return $regulatoryFees;
    }

    protected function pendingCharges($invoice)
    {
        $pendingCharges = collect();

        foreach ($invoice->customer->pendingChargesWithoutInvoice as $pendingChargeWithoutInvoice) {
            $data = [
                'subscription_id' => $pendingChargeWithoutInvoice->subscription_id,
                'product_type'    => '',
                'type'            => $pendingChargeWithoutInvoice->type,
                'start_date'      => $invoice->start_date,
                'description'     => "(Pending Charge) $pendingChargeWithoutInvoice->description",
                'amount'          => $pendingChargeWithoutInvoice->amount,
                'taxable'         => self::TAX_TRUE
            ];

            $pendingCharges->push(
                $invoice->invoiceItem()->create($data)
            );

            $pendingChargeWithoutInvoice->update([
                'invoice_id' => $invoice->id
            ]);

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
            'amount'      => $plan->amount_recurring,  // ToDo: CONFIRM THIS FIRST
            'taxable'     => $plan->taxable,
        ];
        
    }

}
