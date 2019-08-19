<?php

namespace App\Http\Controllers\Api\V1\CronJobs;

use App\Model\Plan;
use App\Model\InvoiceItem;
use App\Model\Invoice;
use App\Model\Device;
use App\Model\Sim;
use App\Model\Addon;
use Carbon\Carbon;
use App\Model\Customer;
use App\Model\Subscription;
use PDF;

trait InvoiceTrait
{
    
    public function addRegulatorFeesToSubscription($subscription, $invoice, $isTaxable, $order = null)
    {
        $amount = 0;
        
        $plan   = $subscription->plan;
        
        if ($plan->regulatory_fee_type == Plan::REGULATORY_FEE_TYPES['fixed_amount']) {
            $amount = $plan->regulatory_fee_amount;

        } elseif ($plan->regulatory_fee_type == Plan::REGULATORY_FEE_TYPES['percentage_of_plan_cost']) {
            
            if($subscription->upgrade_downgrade_status == null){
                $proratedAmount = $order ? $order->planProRate($plan->id) : null;
                $planAmount = $proratedAmount == null ? $plan->amount_recurring : $proratedAmount;
            }else{
                $planAmount = $plan->amount_recurring - $subscription->oldPlan->amount_recurring;
            }

            $regulatoryAmount   = $plan->regulatory_fee_amount/100;
            $subscriptionAmount = $planAmount;

            $amount = $regulatoryAmount * $subscriptionAmount;
        }
        
        return $subscription->invoiceItemDetail()->create([
            'invoice_id'   => $invoice->id,
            // ToDo: Can it also have a product type 
            // and product id. May be product_id can be 
            // invoice_item.product_id
            // Client has not specified in the description
            'product_type' => '',
            'product_id'   => null,
            'type'         => InvoiceItem::INVOICE_ITEM_TYPES['regulatory_fee'],
            'start_date'   => $invoice->start_date,
            'description'  => "(Regulatory Fee) - {$plan->company->regulatory_label}",
            'amount'       => $amount,
            'taxable'      => $isTaxable,
        ]);
    }
    

    public function addTaxesToSubscription($subscription, $invoice, $isTaxable)
    {
        
        $taxPercentage = isset($invoice->customer->stateTax->rate) ? $invoice->customer->stateTax->rate / 100 : 0;

        if ($taxPercentage > 0) {
            $taxesWithSubscriptions = $subscription->invoiceItemDetail->where('taxable', 1)->sum('amount');

            if ($taxesWithSubscriptions > 0) {
                $subscription->invoiceItemDetail()->create(
                    [
                        'invoice_id'   => $invoice->id,
                        'product_type' => '',
                        'product_id'   => null,
                        'type'         => InvoiceItem::INVOICE_ITEM_TYPES['taxes'],
                        'start_date'   => $invoice->start_date,
                        'description'  => "(Taxes)",
                        'amount'       => $taxPercentage * sprintf("%.2f", $taxesWithSubscriptions),
                        'taxable'      => $isTaxable,            
                    ]
                );
            }
        }
    }

    public function addTaxesToStandalone($invoice, $isTaxable, $item)
    {
        
        $taxPercentage = isset($invoice->customer->stateTax->rate) ? $invoice->customer->stateTax->rate / 100 : 0;
        
        if ($taxPercentage > 0) {
            $taxesWithoutSubscriptions  = $invoice->invoiceItem
                                            ->where('subscription_id', null)
                                            ->where('product_type', $item)
                                            ->where('taxable', 1)
                                            ->sum('amount');
                                            
            if ($taxesWithoutSubscriptions > 0) {
                $invoice->invoiceItem()->create(
                    [
                        'invoice_id'   => $invoice->id,
                        'subscription_id' => null,
                        'product_type' => '',
                        'product_id'   => null,
                        'type'         => InvoiceItem::INVOICE_ITEM_TYPES['taxes'],
                        'start_date'   => $invoice->start_date,
                        'description'  => "(Taxes)",
                        'amount'       => $taxPercentage * sprintf("%.2f", $taxesWithoutSubscriptions),
                        'taxable'      => $isTaxable,            
                    ]
                );
            }  
        }
    }

    public function addActivationCharges($subscription, $invoice, $description, $isTaxable)
    {
        $activationFee = Plan::find($subscription->plan_id)->amount_onetime;
        if ($activationFee > 0) {
            $subscription->invoiceItemDetail()->create([
                'invoice_id'   => $invoice->id,
                'product_type' => '',
                'product_id'   => null,
                'type'         => InvoiceItem::INVOICE_ITEM_TYPES['one_time_charges'],
                'start_date'   => $invoice->start_date,
                'description'  => $description,
                'amount'       => $activationFee,
                'taxable'      => $isTaxable, 
            ]);

        }
    }

    public function addTaxesToUpgrade($invoice, $isTaxable, $subscriptionId = null)
    {
        $taxPercentage = $invoice->customer->stateTax->rate / 100;

        $taxesWithoutSubscriptions  = $invoice->invoiceItem
                                        ->where('taxable', 1)
                                        ->sum('amount');
                                        
        if ($taxesWithoutSubscriptions > 0) {
            $invoice->invoiceItem()->create(
                [
                    'invoice_id'   => $invoice->id,
                    'subscription_id' => $subscriptionId,
                    'product_type' => '',
                    'product_id'   => null,
                    'type'         => InvoiceItem::INVOICE_ITEM_TYPES['taxes'],
                    'start_date'   => $invoice->start_date,
                    'description'  => "(Taxes)",
                    'amount'       => $taxPercentage * sprintf("%.2f", $taxesWithoutSubscriptions),
                    'taxable'      => $isTaxable,            
                ]
            );
        }  
    }

    public function generateInvoice($order)
    {
        if ($order && $order->invoice && $order->invoice->invoiceItem) {
            
            $data = '';
            
            $proratedAmount = !isset($order->orderGroup->plan_prorated_amt) ? 0 : $order->orderGroup->plan_prorated_amt;
            
            $data = $order->isOrder($order) ? $this->setOrderInvoiceData($order) : $this->setMonthlyInvoiceData($order);

            $invoice = $this->dataForInvoice($order->invoice, $order);
            
            $invoice = array_merge($data, $invoice);

            if ($order->invoice->type == Invoice::TYPES['one-time']) {
                $pdf = PDF::loadView('templates/onetime-invoice', compact('invoice'));
                return $pdf->download('invoice.pdf');
            
            } else {
                $pdf = PDF::loadView('templates/monthly-invoice', compact('invoice'))->setPaper('letter', 'portrait');                    
                return $pdf->download('invoice.pdf');
                
            }
        } else {
            return 'Sorry, we could not find the details for your invoice';
        }

        
        return 'Sorry, something went wrong please try again later......';
    }

    protected function dataForInvoice($invoice, $order = null)
    {
        if($order){
            $totalCredits           = $order->credits->sum('amount');
            $oldUsedCredits         = $order->credits->first() ? $invoice->creditsToInvoice->where('credit_id', '!=', $order->credits->first()->id)->sum('amount') : $invoice->creditsToInvoice->sum('amount');
            $subscriptionItems      = $this->allSubscriptionData($order);
            $previousBill           = $this->previousBill($order);
        }else{
            $totalCredits           = 0;
            $oldUsedCredits         = 0;
            $subscriptionItems      = 0;
            $previousBill           = 0;
        }
        $invoiceItem            = $invoice->invoiceItem;
        $regulatoryFee          = $invoice->cal_regulatory;
        $stateTax               = $invoice->cal_stateTax;
        $taxes                  = $invoice->cal_taxes;
        $credits                = $invoice->cal_credits;
        $totalCharges           = $invoice->cal_total_charges;
        $oneTimeCharges         = $invoice->cal_onetime;
        $usageCharges           = $invoice->cal_usage_charges;
        $planCharges            = $invoice->cal_plan_charges;
        $totalAccountCharges    = $invoiceItem->whereIn('type',
                                    [
                                        InvoiceItem::TYPES['one_time_charges'],
                                        InvoiceItem::TYPES['regulatory_fee'],
                                        InvoiceItem::TYPES['taxes'],
                                    ]
                                )->sum('amount');
        $serviceChargesProrated = $planCharges + $oneTimeCharges + $usageCharges;
        $serviceCharges         = $invoice->cal_service_charges;
        $shippingFee            = $invoiceItem->where('description', 'Shipping Fee')->sum('amount');
        $shippingFeeStandalone  = $invoiceItem->where('subscription_id', null)->where('description', 'Shipping Fee')->sum('amount');
        $tax                    = $taxes;
        $totalCreditsToInvoice  = $invoice->creditsToInvoice->sum('amount');
        $totalCoupons           = $invoiceItem->where('type', self::COUPONS)->sum('amount');
        $accountChargesDiscount = $totalAccountCharges - $totalCoupons - $shippingFee;
        $totalLineCharges       = $planCharges + $oneTimeCharges + $taxes + $usageCharges - $totalCoupons;
        $standalone             = $invoiceItem->where('subscription_id', null);
        $standaloneItems        = $standalone->where('type', InvoiceItem::TYPES['one_time_charges']);
        $standaloneTaxes        = $standalone->where('type', InvoiceItem::TYPES['taxes'])->sum('amount');
        $standaloneRegulatory   = $standalone->where('type', InvoiceItem::TYPES['regulatory_fee'])->sum('amount');
        $standaloneCoupons      = $standalone->where('type', InvoiceItem::TYPES['coupon'])->sum('amount');
        $standaloneTotal        = $standalone->where('type', '!=', InvoiceItem::TYPES['coupon'])->sum('amount');
                                        
        $invoice = [
            'invoice_type'                  =>   $invoice->type,
            'service_charges'               =>   self::formatNumber($serviceCharges),
            'taxes'                         =>   self::formatNumber($taxes),
            'credits'                       =>   self::formatNumber($credits),
            'total_charges'                 =>   self::formatNumber($totalAccountCharges),
            'total_one_time_charges'        =>   self::formatNumber($oneTimeCharges),
            'total_usage_charges'           =>   self::formatNumber($usageCharges),
            'plan_charges'                  =>   self::formatNumber($planCharges),
            'serviceChargesProrated'        =>   self::formatNumber($serviceChargesProrated),
            'regulatory_fee'                =>   self::formatNumber($regulatoryFee),
            'state_tax'                     =>   self::formatNumber($stateTax),
            'total_account_charges'         =>   self::formatNumber($oneTimeCharges + $taxes),
            'subtotal'                      =>   self::formatNumber($invoice->subtotal),
            'shipping_fee'                  =>   self::formatNumber($shippingFee),
            'tax_and_shipping'              =>   self::formatNumber($tax),
            'standalone_data'               =>   $this->setStandaloneItemData($order),
            'total_old_credits'             =>   self::formatNumber($oldUsedCredits),
            'total_credits_to_invoice'      =>   self::formatNumber($totalCreditsToInvoice),
            'total_payment'                 =>   self::formatNumber($order->credits->sum('amount')),
            'total_used_credits'            =>   self::formatNumber($totalCredits + $oldUsedCredits),
            'date_payment'                  =>   $order->credits->first() ? Carbon::parse($order->credits->first()->date)->format('m/d/Y') : '',
            'date_credit'                   =>   $invoice->creditsToInvoice->first() ? Carbon::parse($invoice->creditsToInvoice->first()->created_at)->format('m/d/Y') : '',
            'credits_and_coupons'           =>   self::formatNumber($totalCreditsToInvoice + $totalCoupons),
            'total_coupons'                 =>   self::formatNumber($totalCoupons),
            'account_charges_discount'      =>   self::formatNumber($accountChargesDiscount),
            'total_line_charges'            =>   self::formatNumber($totalLineCharges),
            'total_account_summary_charge'  =>   self::formatNumber($planCharges + $oneTimeCharges + $usageCharges + $tax - $credits),
            'customer_auto_pay'             =>   $order->customer->auto_pay,
            'company_logo'                  =>   $order->customer->company->logo,
            'reseller_phone_number'         =>   $this->phoneNumberFormatted($order->customer->company->support_phone_number),
            'reseller_domain'               =>   $order->customer->company->url,
            'standalone_items'              =>   $this->getItemDetails($standaloneItems),
            'one_time_standalone'           =>   self::formatNumber($standaloneItems->sum('amount')),
            'previous_bill'                 =>   $previousBill,
            'standalone_tax'                =>   self::formatNumber($standaloneTaxes),
            'standalone_regulatory'         =>   self::formatNumber($standaloneRegulatory),
            'standalone_shipping'           =>   self::formatNumber($shippingFeeStandalone),
            'standalone_total_taxes_fees'   =>   self::formatNumber($standaloneTaxes + $standaloneRegulatory),
            'standalone_coupons'            =>   self::formatNumber($standaloneCoupons),
            'standalone_total'              =>   self::formatNumber($standaloneTotal - $standaloneCoupons),
            'subscription_per_page'         =>   $subscriptionItems,
            'max_pages'                     =>   count($subscriptionItems) +  2
        ];
        return $invoice;
    }

    protected function phoneNumberFormatted($number)
    {
        $number = preg_replace("/[^\d]/","",$number);
    
        $length = strlen($number);

        if($length == 10) {
            $number = preg_replace("/^1?(\d{3})(\d{3})(\d{4})$/", "$1-$2-$3", $number);
        }
            
        return $number;
    }


    protected function getItemDetails($items)
    {

        $devices     = [];
        $sims        = [];
    
        foreach ($items as $item) {

            if ($item['product_type'] == InvoiceItem::PRODUCT_TYPE['device'] && $item['amount']) {

                $name      = Device::find($item['product_id'])->name;
                $devices[] = ['name' => $name, 'amount' => $item['amount']];

            }

            if ($item['product_type'] == InvoiceItem::PRODUCT_TYPE['sim'] && $item['amount']) {

                $name      = Sim::find($item['product_id'])->name;
                $sims[]    = ['name' => $name, 'amount' => $item['amount']];

            }

        }
        
        return ['devices' => $devices, 'sims' => $sims];

    }


    protected function plans($order)
    {
        $allPlans = $order->invoice->invoiceItem
            ->where('type', InvoiceItem::TYPES['plan_charges'])
            ->where('amount', '!=', null);
        
        foreach ($allPlans as $plan) {
            $plans[]    = ['id' => $plan->product_id, 'name' => Plan::find($plan->product_id)->name, 'amount' => $plan->amount];
        }

        return !empty($plans) ? $plans : $plans = [['name' => 'No plans', 'amount' => 0]];
    }

    protected function addons($order)
    {
        $allAddonIds = $order->invoice->invoiceItem
            ->where('type', InvoiceItem::TYPES['feature_charges']);
  
        foreach ($allAddonIds as $addon) {
            $addons[]   = ['id' => $addon->product_id, 'name' => Addon::find($addon->product_id)->name, 'amount' => $addon->amount];
        }      

        return !empty($addons) ? $addons : $addons = [['name' => 'No addons', 'amount' => 0]];
    }


    protected function invoiceData($order)
    {
        if ($order->invoice->status == Invoice::INVOICESTATUS['closed']) {
            $payment = self::formatNumber($order->invoice->creditToInvoice->first()->amount);
            $creditToInvoice = $order->invoice->creditToInvoice->first()->amount;
            $paymentMethod = $order->invoice->creditToInvoice->first()->credit->description;
            $paymentDate   = $order->invoice->creditToInvoice->first()->credit->date;
        } else {
            $payment = 0;
            $creditToInvoice = 0;
            $paymentMethod = '';
            $paymentDate = '';
        }
        $arr = [
            'invoice_num'           => $order->invoice->id,
            'subscriptions'         => [],
            'end_date'              => Carbon::parse($order->invoice->end_date)->format('m/d/Y'),
            'due_date'              => Carbon::parse($order->invoice->due_date)->format('m/d/Y'),
            'total_due'             => self::formatNumber($order->invoice->total_due),
            'subtotal'              => $order->invoice->subtotal,
            'invoice_item'          => $order->invoice->invoiceItem,
            'today_date'            => Carbon::parse($order->invoice->created_at)->toFormattedDateString(), // $this->carbon->toFormattedDateString(),
            'company_name'          => $order->customer->company_name,
            'customer_name'         => $order->customer->full_name,
            'customer_address'      => $order->customer->shipping_address1,
            'customer_zip_address'  => $order->customer->zip_address,
            'payment'               => $payment,
            'credit_to_invoice'     => $creditToInvoice,
            'old_credits'           => self::formatNumber(0),
            'total_credits'         => self::formatNumber($creditToInvoice),
            'payment_method'        => $paymentMethod,
            'payment_date'          => $paymentDate,
            'regulatory_fee'        => $order->invoice->invoiceItem,
            'start_date'            => Carbon::parse($order->invoice->start_date)->format('m/d/Y'),
            'end_date'              => Carbon::parse($order->invoice->end_date)->format('m/d/Y'),
        ];
        return $arr;
    }

    protected function setStandaloneItemData($order)
    {
        $standaloneItems = $order->invoice->invoiceItem;
        $deviceAndSim = $standaloneItems->whereIn('product_type',
            [
                self::DEVICE_TYPE,
                self::SIM_TYPE
            ]
        )->where('subscription_id', 0)->sum('amount');
        $tax          = $standaloneItems->where('type', self::TAXES)->where('subscription_id', 0)->sum('amount');
        $shipping     = $standaloneItems->where('description', 'Shipping Fee')->where('subscription_id', 0)->sum('amount');
        $coupons      = $standaloneItems->where('type', InvoiceItem::TYPES['coupon'])->sum('amount');
        
        $standalone = [
            'standalone_onetime_charges'  => self::formatNumber($deviceAndSim + $shipping),
            'standalone_shipping_fee'     => self::formatNumber($shipping),
            'taxes'                       => self::formatNumber($tax),
            'coupons'                     => self::formatNumber($coupons),  
            'total'                       => self::formatNumber($tax + $deviceAndSim + $shipping - $coupons),
        ];
        
        return $standalone;
    }
    

    protected function setOrderInvoiceData($order)
    {
        $arr = $this->invoiceData($order);

        foreach ($order->subscriptions as $subscription) {
            if($subscription->upgrade_downgrade_status){
                $planCharges    = $this->getSubscriptionsData($order, $subscription->id, 'plan_charges');
                $addonCharges   = $this->getSubscriptionsData($order, $subscription->id, 'feature_charges');
                $planCharges    = $planCharges + $addonCharges;
                $onetimeCharges = 0;
                $usageCharges   = $subscription->cal_usage_charges;
                $tax            = $this->getSubscriptionsData($order, $subscription->id, 'taxes');
                $total          = $order->invoice->invoiceItem
                                    ->where('subscription_id', $subscription->id)
                                    ->sum('amount');
                $shippingFee    = 0;
            }else{
                $planCharges    = $subscription->cal_plan_charges;
                $onetimeCharges = $subscription->cal_onetime_charges;
                $usageCharges   = $subscription->cal_usage_charges;
                $tax            = $subscription->cal_taxes;
                $total          = $order->invoice->invoiceItem
                                    ->where('subscription_id', $subscription->id)
                                    ->sum('amount');
                $shippingFee    = $order->invoice->invoiceItem
                                                ->where('subscription_id', $subscription->id)
                                                ->where( 'description', 'Shipping Fee')->sum('amount');
            }
            $subscriptionData = [
                'subscription_id' => $subscription->id,
                'plan_charges'    => self::formatNumber($planCharges),
                'onetime_charges' => self::formatNumber($onetimeCharges),
                'phone'           => $subscription->phone_number ? $this->phoneNumberFormatted($subscription->phone_number) : 'Pending',
                'usage_charges'   => $usageCharges,
                'tax'             => $tax,
                'total'           => self::formatNumber($total)
            ];
            array_push($arr['subscriptions'], $subscriptionData);
        }
        return $arr;
    }

    protected function setMonthlyInvoiceData($order)
    {
        $arr = $this->invoiceData($order);
        
        foreach ($order->invoice->invoiceItem as $item) { $subscriptions[] = $item->subscription_id; }
        foreach (array_count_values($subscriptions) as $id => $count) {

            $phone = Subscription::where('id', $id)->pluck('phone_number')->first();

            $planCharges        = $this->getSubscriptionsData($order, $id, 'plan_charges');
            $addonCharges       = $this->getSubscriptionsData($order, $id, 'feature_charges');
            $totalTax           = $this->getSubscriptionsData($order, $id, 'taxes');
            $totalRegulatory    = $this->getSubscriptionsData($order, $id, 'regulatory_fee');
            $totalCoupons       = $this->getSubscriptionsData($order, $id, 'coupon');
            $totalManual        = $this->getSubscriptionsData($order, $id, 'manual');
            $total              = $order->invoice->invoiceItem->where('subscription_id', $id)->where('type', '!=', 6)->sum('amount');

            $subscriptionData = [
                'subscription_id'       => $id,
                'plan_charges'          => self::formatNumber($this->getSubscriptionsData($order, $id, 'plan_charges')),
                'addonCharges'          => self::formatNumber($this->getSubscriptionsData($order, $id, 'feature_charges')),
                'regulatory_fee'        => self::formatNumber($this->getSubscriptionsData($order, $id, 'regulatory_fee')),
                'tax'                   => self::formatNumber($this->getSubscriptionsData($order, $id, 'taxes')),
                'onetime_charges'       => self::formatNumber($this->getSubscriptionsData($order, $id, 'one_time_charges')),
                'usage_charges'         => self::formatNumber($this->getSubscriptionsData($order, $id, 'usage_charges')),
                'coupon'                => self::formatNumber($this->getSubscriptionsData($order, $id, 'coupon')),
                'manual'                => self::formatNumber($this->getSubscriptionsData($order, $id, 'manual')),
                'phone'                 => $this->phoneNumberFormatted($phone),
                'plan_and_addons'       => self::formatNumber($planCharges + $addonCharges),
                'tax_and_regulatory'    => self::formatNumber($totalTax + $totalRegulatory),
                'total_discounts'       => self::formatNumber($totalCoupons + $totalManual),
                'total'                 => self::formatNumber($total - $totalCoupons),

            ];

            array_push($arr['subscriptions'], $subscriptionData);

        }

        return $arr;
    }

    protected function getSubscriptionsData($order, $id, $type)
    {
        return $order->invoice->invoiceItem->where('subscription_id', $id)->where('type' , InvoiceItem::TYPES[$type])->sum('amount');
    }

    public static function formatNumber($amount)
    {
        return number_format($amount, 2);
    }

    public function allSubscriptionData($order) 
    {
        $data = [];
        $invoiceType   = $order->invoice->type;
        $subscriptions = $invoiceType == 2 ? $order->subscriptions : Customer::find($order->customer_id)->billableSubscriptions;
        $pageCount     = 3;
        
        foreach ($subscriptions as $subscription) {

            $plan               = Plan::find($subscription->plan_id);

            $planCharges        = 0;

            if ($invoiceType == 2) {

                $planCharges        = $order->planProrate($plan->id)  ? $order->planProrate($plan->id) : $plan->amount_recurring;
                
            } else {

                $planCharges        = $plan->amount_recurring;

            }

            $subscriptionItems  = $order->invoice->invoiceItem->where('subscription_id', $subscription->id);
            
            $addonCharges       = $subscriptionItems->where('type', InvoiceItem::TYPES['feature_charges'])->sum('amount');

            $addonsData         = $this->addonData($subscription, $order, $invoiceType);

            $device             = $subscriptionItems
                                    ->where('type', InvoiceItem::TYPES['one_time_charges'])
                                    ->where('product_type', InvoiceItem::PRODUCT_TYPE['device'])->first();
                                    
            $deviceData         = isset($device) ? Device::find($device->product_id) : null;

            $sim                = $subscriptionItems
                                    ->where('type', InvoiceItem::TYPES['one_time_charges'])
                                    ->where('product_type', InvoiceItem::PRODUCT_TYPE['sim'])->first();

            $simData            = isset($sim) ? Sim::find($sim->product_id) : null;

            $taxes              = $subscriptionItems->where('type', InvoiceItem::TYPES['taxes'])->sum('amount');

            $regulatoryFee      = $subscriptionItems->where('type', InvoiceItem::TYPES['regulatory_fee'])->sum('amount');

            $usageCharges       = $subscriptionItems->where('type', InvoiceItem::TYPES['usage_charges'])->sum('amount');

            $activationFee      = $subscriptionItems->where('type', InvoiceItem::TYPES['one_time_charges'])
                                    ->where('description', 'Activation Fee')
                                    ->sum('amount');

            $coupons            = $subscriptionItems->where('type', InvoiceItem::TYPES['coupon'])->sum('amount');

            $shippingFee        = $subscriptionItems->where('description', 'Shipping Fee')->sum('amount');

            $totalCharges       = $subscriptionItems->whereNotIn('type', InvoiceItem::TYPES['coupon'])->sum('amount');
            
            $data[] = [
                'subscription_id'               => $subscription->id,
                'phone'                         => $subscription->phone_number ? $this->phoneNumberFormatted($subscription->phone_number) : 'Pending',
                'plan_name'                     => $plan->name,
                'plan_charges'                  => self::formatNumber($planCharges),
                'addons'                        => $addonsData,
                'plan_and_addons_total'         => self::formatNumber($planCharges + $addonCharges),
                'device_name'                   => isset($deviceData->name) ? $deviceData->name : null,
                'device_charges'                => isset($deviceData->amount_w_plan) ? self::formatNumber($deviceData->amount_w_plan) : null,
                'sim_name'                      => isset($simData->name) ? $simData->name : null,
                'sim_charges'                   => self::formatNumber(isset($simData->amount_w_plan) ? $simData->amount_w_plan : 0),
                'activation_fee'                => self::formatNumber($activationFee),
                'total_one_time'                => self::formatNumber($subscriptionItems->where('type', InvoiceItem::TYPES['one_time_charges'])->sum('amount')),
                'usage_charges'                 => self::formatNumber($usageCharges),
                'regulatory_fee'                => self::formatNumber($regulatoryFee),
                'subscription_tax'              => self::formatNumber($taxes),
                'total_tax_and_fee'             => self::formatNumber($taxes + $regulatoryFee),
                'coupons'                       => self::formatNumber($coupons),
                'shipping_fee'                  => self::formatNumber($shippingFee),
                'total_subscription_charges'    => self::formatNumber($totalCharges - $coupons),
                'page_count'                    => $pageCount,
            ];

            $pageCount++;

        }

        return $data;

    }

    public function addonData($subscription, $order, $invoiceType)
    {
        $subscriptionAddon  = $subscription->subscriptionAddon;
        $data   = [];
        $total  = [];
        foreach ($subscriptionAddon as $addon) {
            $addonData      = Addon::find($addon->addon_id);
            $addonName      = $addonData->name;
            $addonCharges   = 0;
            if ($invoiceType == 2) {
                $addonCharges = $order->addonProRate($addonData->id) ?: $addonData->amount_recurring;
            } else {
                $addonCharges = $addonData->amount_recurring;
            }
            $data[]         = [
                'name'              => $addonName, 
                'charges'           => self::formatNumber($addonCharges), 
                'subscription_id'   => $addon->subscription_id
            ];
        }
        return $data;
    }

    protected function previousBill($order)
    {
        $lastInvoiceId  = $order->customer->invoice
                                ->where('type', Invoice::TYPES['monthly'])
                                ->where('id', '!=', $order->invoice_id)
                                ->max('id');
        if ($lastInvoiceId && $order->invoice->type == Invoice::TYPES['one-time']) {

            $lastInvoice        = Invoice::find($lastInvoiceId);

            $previousTotalDue   = $lastInvoice->subtotal;
            $amountPaid         = $lastInvoice->creditsToInvoice->sum('amount');
            $pending            = $previousTotalDue > $amountPaid ? $previousTotalDue - $amountPaid : 0;

            return [
                'previous_amount'    => self::formatNumber($previousTotalDue),
                'previous_payment'   => self::formatNumber($amountPaid),
                'previous_pending'   => self::formatNumber($pending)
            ];
        }
    }

}
