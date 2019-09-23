<?php

namespace App\Http\Controllers\Api\V1\CronJobs;

use Carbon\Carbon;
use App\Model\Plan;
use App\Model\Order;
use App\Model\Addon;
use App\Model\Coupon;
use App\Model\Company;
use App\Model\Invoice;
use App\Model\Customer;
use App\Model\InvoiceItem;
use Illuminate\Http\Request;
use App\Model\CouponProduct;
use App\Events\MonthlyInvoice;
use App\Model\CouponProductType;
use App\Model\SystemGlobalSetting;
use App\Http\Controllers\BaseController;
use App\libs\Constants\ConstantInterface;
use App\Http\Controllers\Api\V1\Traits\InvoiceTrait;
use Exception;

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
    public function generateMonthlyInvoice(Request $request)
    {
        try {
            // $customers = Customer::shouldBeGeneratedNewInvoices();
            $customers = Customer::shouldBeGeneratedNewInvoices();
            
            foreach ($customers as $customer) {
                try {
                
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
                        
                        $dueDate = Carbon::parse($invoice->start_date)->subDay();

                        $dueDateChange = $invoice->update([
                            'due_date' => $dueDate
                        ]);

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
                        
                        $subtotal = str_replace(',', '',number_format($monthlyCharges + $totalPendingCharges - $couponDiscountTotal, 2));

                        $invoiceUpdate = $invoice->update(compact('subtotal'));

                        $totalDue = $this->applyCredits($customer, $invoice);

                        $invoice->update(['total_due' => $totalDue]);

                        if ($totalDue == 0) {
                            $invoice->update(['status' => Invoice::INVOICESTATUS['closed']]);
                        }
            
                        $insertOrder = $this->insertOrder($invoice);
                        
                        $order       = Order::where('invoice_id', $invoice->id)->first();

                        $request->headers->set('authorization', $order->company->api_key);

                        $invoiceSavePath = SystemGlobalSetting::first()->upload_path;
                        
                        $fileSavePath = $invoiceSavePath.'/uploads/'.$order->company->id.'/invoice-pdf/';

                        $this->generateInvoice($order, $fileSavePath, $request);
                        
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

                } catch (Exception $e) {
                    \Log::info($e->getMessage(). ' on line: '.$e->getLine(). ' inside monthly invoice controller');
                }
            }

            return $this->respond($this->response);
        } catch (Exception $e) {
            \Log::info($e->getMessage(). ' on line: '.$e->getLine(). ' inside monthly invoice controller');
        }
    }

    public function applyCredits($customer, $invoice)
    {
        $totalDue = $invoice->subtotal;
        if (isset($totalDue) && $totalDue) {
            foreach ($customer->creditsNotAppliedCompletely as $creditNotAppliedCompletely) {
                try {
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
                } catch (Exception $e) {
                    \Log::info($e->getMessage(). ' on line: '.$e->getLine(). ' inside monthly invoice controller');
                }
            }
    
            return $totalDue;
        }
    }

    public function addTaxes($customer, $invoice, $billableSubscriptionInvoiceItems)
    {
        try {
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
                    'amount'          => number_format($amount, 2),
                    'taxable'         => self::TAX_FALSE
                ];
    
                $taxes->push(
                    $invoice->invoiceItem()->create($data)
                );
    
            }
    
            return $taxes;
        } catch (Exception $e) {
            \Log::info($e->getMessage(). ' on line: '.$e->getLine(). ' inside monthly invoice controller');
        }
        
    }

    public function customerAccountCoupons($customer, $invoice)
    {
        $customerCouponRedeemable = $customer->customerCouponRedeemable;
        if ($customerCouponRedeemable) {
            foreach ($customerCouponRedeemable as $customerCoupon) {
                try {
                    $coupon = $customerCoupon->coupon;
                    
                    if($customerCoupon->cycles_remaining == 0) continue;
                    
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
                            if($amount === false || $amount == 0) continue;

                            $invoice->invoiceItem()->create([
                                'subscription_id' => $subscription->id,
                                'product_type'    => '',
                                'product_id'      => $coupon->id,
                                'type'            => InvoiceItem::TYPES['coupon'],
                                'description'     => "(Customer Account Coupon) $coupon->code",
                                'amount'          => str_replace(',', '',number_format($amount, 2)),
                                'start_date'      => $invoice->start_date,
                                'taxable'         => self::TAX_FALSE,
                            ]);
                        }
                        if ($customerCoupon['cycles_remaining'] > 0) {
                            $customerCoupon->update(['cycles_remaining' => $customerCoupon['cycles_remaining'] - 1]);
                        }
                        // ToDo: Add logs,Order not provided in requirements
                    }
                } catch (Exception $e) {
                    \Log::info($e->getMessage(). ' on line: '.$e->getLine(). ' inside monthly invoice controller');
                }
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
        try {
            $plan           = $subscription->plan;
            $addons         = $subscription->subscriptionAddon;

            if ($addons) {
                $addonAmount = $this->getAddonAmountForCoupons($addons);
            }

            // Had to use continue, so used if else 
            // and not switch statement
            if($coupon->class == Coupon::CLASSES['APPLIES_TO_SPECIFIC_TYPES']){
                $couponProductTypeForPlans      = $coupon->couponProductTypes->where('type', CouponProductType::TYPES['plan']);
                $couponProductTypeForThisPlan   = $couponProductTypeForPlans->where('sub_type', '!=', 0)->where('sub_type', $plan->type)->first();
                $couponProductTypeForAddons     = $coupon->couponProductTypes->where('type', CouponProductType::TYPES['addon'])->first();
                
                // Coupon doesnot support plans or 
                // doesnot supports this plan
                //if(!$couponProductTypeForPlans || !$couponProductTypeForThisPlan ) return false;
                $addonTotalAmount   = 0;
                $planAmount         = 0;
                
                if($coupon->fixed_or_perc == Coupon::FIXED_PERC_TYPES['percentage']){
                    $addonTotalAmount   = $addonAmount['total_amount'] && $couponProductTypeForAddons ? $couponProductTypeForAddons->amount * $addonAmount['total_amount'] / 100 : 0;

                    if ($couponProductTypeForThisPlan) {
                        $planAmount = $couponProductTypeForThisPlan->amount * $plan->amount_recurring / 100;
                    } else {
                        if ($couponProductTypeForPlans) {
                            $planAmount = $couponProductTypeForPlans->first()->amount * $plan->amount_recurring / 100;
                        }
                    }

                    $amount = $planAmount + $addonTotalAmount;
                    
                } else {
                    $addonTotalAmount   = $addonAmount['count'] && $couponProductTypeForAddons ? $couponProductTypeForAddons->amount * $addonAmount['count'] : 0;
                    if ($couponProductTypeForThisPlan) {
                        $planAmount = $couponProductTypeForThisPlan->amount;
                    } else {
                        if ($couponProductTypeForPlans) {
                            $planAmount = $couponProductTypeForPlans->first()->amount;
                        }
                    }

                    $amount = $planAmount + $addonTotalAmount;
                }

            } elseif($coupon->class == Coupon::CLASSES['APPLIES_TO_SPECIFIC_PRODUCT']){
                $couponProductsForPlans         = $coupon->couponProducts->where('product_type', CouponProduct::PRODUCT_TYPES['plan']);
                $couponProductForThisPlan       = $couponProductsForPlans->where('product_id', $plan->id)->first();

                $addonTotalAmount   = [];
                $planAmount         = 0;
                
                //Checks if plan coupon supports plan
                if($couponProductForThisPlan) {
                    if ($coupon->fixed_or_perc == Coupon::FIXED_PERC_TYPES['percentage']) {
                        $planAmount = $couponProductForThisPlan->amount * $plan->amount_recurring / 100;
                    } else {
                        $planAmount = $couponProductForThisPlan->amount;
                    }
                }

                //Checks if plan coupon supports addons
                foreach ($addons as $subAddon) {
                    try {
                        $addon  = Addon::find($subAddon->addon_id);
                        $couponProductForAddons     = $coupon->couponProducts->where('product_type', CouponProductType::TYPES['addon'])->where('product_id', $addon->id)->first();
                        
                        if ($couponProductForAddons) {
                            if ($coupon->fixed_or_perc == Coupon::FIXED_PERC_TYPES['percentage']) {
                                $addonTotalAmount[] = $couponProductForAddons->amount * $addon->amount_recurring / 100;
                            } else {
                                $addonTotalAmount[] = $couponProductForAddons->amount;
                            }
                        }
                    } catch (Exception $e) {
                        \Log::info($e->getMessage(). ' on line: '.$e->getLine(). ' inside monthly invoice controller');
                    }
                }
                //Add both amounts
                $amount = $planAmount + array_sum($addonTotalAmount);

            } else {
                $amount = $addonAmount['count'] ? $coupon->amount + ($coupon->amount * $addonAmount['count']) : $coupon->amount;
                if($coupon->fixed_or_perc == Coupon::FIXED_PERC_TYPES['percentage']){
                    $addonTotalAmount   = $addonAmount['total_amount'] ? $addonAmount['total_amount'] : 0;
                    $amount             = ($plan->amount_recurring + $addonTotalAmount) * ($coupon->amount/100);
                }
            }

            return $amount;
        } catch (Exception $e) {
            \Log::info($e->getMessage(). ' on line: '.$e->getLine(). ' inside monthly invoice controller');
        }
    }


    public function customerSubscriptionCoupons($invoice, $subscriptions)
    {
        foreach($subscriptions as $subscription){
            
            $subscriptionCouponRedeemable = $subscription->subscriptionCouponRedeemable;

            // Subscription doesnot has any coupons
            if(!$subscriptionCouponRedeemable) continue;

            foreach ($subscriptionCouponRedeemable as $subscriptionCoupon) {
                try {
                    $coupon = $subscriptionCoupon->coupon;

                    if($subscriptionCoupon->cycles_remaining == 0) continue;

                    $coupon->load('couponProductTypes', 'couponProducts');

                    $amount = $this->customerAccountCouponAmount($subscription, $coupon);

                    // Possibility of returning 0 as well but
                    // returns false when coupon is not applicable
                    if($amount === false || $amount == 0) continue;

                    $invoice->invoiceItem()->create([
                        'subscription_id' => $subscription->id,
                        'product_type'    => '',
                        'product_id'      => null,
                        'type'            => InvoiceItem::TYPES['coupon'],
                        'description'     => "(Subscription Coupon) $coupon->code",
                        'amount'          => str_replace(',', '', number_format($amount, 2)),
                        'start_date'      => $invoice->start_date,
                        'taxable'         => self::TAX_FALSE,
                    ]);

                    if ($subscriptionCoupon['cycles_remaining'] > 0) {
                        $subscriptionCoupon->update(['cycles_remaining' => $subscriptionCoupon['cycles_remaining'] - 1]);
                    }
                } catch (Exception $e) {
                    \Log::info($e->getMessage(). ' on line: '.$e->getLine(). ' inside monthly invoice controller');
                }
                    
            }

        }

    }

    protected function getAddonAmountForCoupons($addons)
    {
        $totalAmount = [];
        $count       = 0;
        $ids         = [];
        foreach ($addons as $addon) {
            $totalAmount[] = Addon::find($addon->addon_id)->amount_recurring;
            $count += 1;
            $ids[]  = $addon->id;
        }
        return ['total_amount' => array_sum($totalAmount), 'count' => $count];
    }

    protected function insertOrder($invoice)
    {
        $hash = md5(time().rand());
    
        if ($invoice->type === Invoice::TYPES['monthly']) {
            $count = Order::where('company_id', Company::Id['britex'])->max('order_num');
            Order::create([
                'status'        => 1,
                'invoice_id'    => $invoice->id,
                'hash'          => $hash,
                'order_num'     => $count+1,
                'company_id'    => Company::Id['britex'],
                'customer_id'   => $invoice->customer_id,
                'date_processed' => Carbon::today(),
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
            try {
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
            } catch (Exception $e) {
                \Log::info($e->getMessage(). ' on line: '.$e->getLine(). ' inside monthly invoice controller. Subscription id: '.$billableSubscription->id);
            }
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
            if ($subscription) {
                foreach ( $subscription->billableSubscriptionAddons as $subscriptionAddon ) {
                    try {
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
                    } catch (Exception $e) {
                        \Log::info($e->getMessage(). ' on line: '.$e->getLine(). ' inside monthly invoice controller');
                    }
                }
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
            try {
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
            } catch (Exception $e) {
                \Log::info($e->getMessage(). ' on line: '.$e->getLine(). ' inside monthly invoice controller');
            }

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
