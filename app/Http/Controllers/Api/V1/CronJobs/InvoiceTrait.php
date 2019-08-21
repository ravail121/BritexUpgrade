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
            
            $data = $this->dataForInvoice($order);
            
            if ($order->invoice->type == Invoice::TYPES['one-time']) {

                $ifUpgradeOrDowngradeInvoice = $this->ifUpgradeOrDowngradeInvoice($order);

                if ($ifUpgradeOrDowngradeInvoice['upgrade_downgrade_status']) {
                    $pdf = PDF::loadView('templates/onetime-invoice', compact('data', 'ifUpgradeOrDowngradeInvoice'));
                    return $pdf->download('invoice.pdf');
                } else {
                    $pdf = PDF::loadView('templates/onetime-invoice', compact('data'));
                    return $pdf->download('invoice.pdf');
                }

            } else {
                $subscriptionIds = $order->invoice->invoiceItem->pluck('subscription_id')->toArray();
                $subscriptions   = [];
                foreach (array_unique($subscriptionIds) as $id) {   
                    array_push($subscriptions, Subscription::find($id));
                }
                $pdf = PDF::loadView('templates/monthly-invoice', compact('data', 'subscriptions'))->setPaper('letter', 'portrait');                    
                return $pdf->download('invoice.pdf');
                
            }
        } else {
            return 'Sorry, we could not find the details for your invoice';
        }

        
        return 'Sorry, something went wrong please try again later......';
    }

    protected function dataForInvoice($order)
    {                                    

        $invoice = [
            'order'                         =>   $order,
            'invoice'                       =>   $order->invoice,
            'standalone_items'              =>   $order->invoice->invoiceItem->where('subscription_id', null),
            'previous_bill'                 =>   $this->previousBill($order)
        ];
        return $invoice;
    }


    protected function previousBill($order)
    {
        $lastInvoiceId  = $order->customer->invoice
                                ->where('type', Invoice::TYPES['monthly'])
                                ->where('id', '!=', $order->invoice_id)
                                ->max('id');
        if ($lastInvoiceId) {

            $lastInvoice        = Invoice::find($lastInvoiceId);

            $previousTotalDue   = $lastInvoice->subtotal;
            $amountPaid         = $lastInvoice->creditsToInvoice->sum('amount');
            $pending            = $previousTotalDue > $amountPaid ? $previousTotalDue - $amountPaid : 0;

            return [
                'previous_amount'    => number_format($previousTotalDue, 2),
                'previous_payment'   => number_format($amountPaid, 2),
                'previous_pending'   => number_format($pending, 2)
            ];
        }
    }

    protected function ifUpgradeOrDowngradeInvoice($order)
    {
        $subscriptionId = array_unique($order->invoice->invoiceItem->pluck('subscription_id')->toArray());
        if (count($subscriptionId) == 1) {
            $subscription = Subscription::find($subscriptionId[0]);
            if ($subscription->upgrade_downgrade_status) {
                $addonsIds = $order->invoice->invoiceItem->where('type', InvoiceItem::TYPES['feature_charges'])->pluck('product_id');

                $planData = [
                    'name' => Plan::find($subscription->plan_id)->name,
                    'amount' => $subscription->invoiceItemDetail
                                ->where('invoice_id', $order->invoice->id)
                                ->where('type', InvoiceItem::TYPES['plan_charges'])
                                ->where('product_id', $subscription->plan_id)
                                ->sum('amount'),
                ];

                $addonData = [];
                if (count($addonsIds)) {
                    foreach ($addonsIds as $id) {
                        $ifAddonsAdded = $order->invoice->invoiceItem
                                            ->where('type', InvoiceItem::TYPES['feature_charges'])
                                            ->where('product_id', $id);
                        if (count($ifAddonsAdded)) {
                            foreach ($ifAddonsAdded as $addedAddon) {
                                array_push($addonData, [
                                    'name' => Addon::find($addedAddon->product_id)->name,
                                    'amount' => $addedAddon->amount,
                                    'description' => $addedAddon->description
                                ]);
                            }
                        }
                    }
                }
                return [
                    'addon_data' => $addonData,
                    'plan_data'  => $planData,
                    'upgrade_downgrade_status' => true
                ];
            } else {
                return [
                    'upgrade_downgrade_status' => false
                ];
            }
        }
    }
    

}