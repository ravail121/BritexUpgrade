<?php

namespace App\Http\Controllers\Api\V1\CronJobs;

use App\Model\Plan;
use App\Model\InvoiceItem;


trait InvoiceTrait
{
    
    public function addRegulatorFeesToSubscription($subscription, $invoice, $isTaxable)
    {
        $amount = 0;
        $plan   = $subscription->plan;

        if ($plan->regulatory_fee_type == Plan::REGULATORY_FEE_TYPES['fixed_amount']) {
            $amount = $plan->regulatory_fee_amount;

        } elseif ($plan->regulatory_fee_type == Plan::REGULATORY_FEE_TYPES['percentage_of_plan_cost']) {

            $regulatoryAmount   = $plan->regulatory_fee_amount/100;
            $subscriptionAmount = $plan->amount_recurring;

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

    public function addTaxes($subscription, $invoice, $isTaxable)
    {
        $amount = 0;
        $taxPercentage = $invoice->customer->stateTax->rate / 100;
        //$plan   = $subscription->plan;
        //$planAmount = Plan::find($subscription->plan->id);

        foreach ($invoice->InvoiceItem as $item) {
            if ($item->taxable == 1) {
                $taxes[] = $item->amount;
            }            
        }

        $taxes = [
            'invoice_id'   => $invoice->id,
            'product_type' => '',
            'product_id'   => null,
            'type'         => InvoiceItem::INVOICE_ITEM_TYPES['taxes'],
            'start_date'   => $invoice->start_date,
            'description'  => "(Taxes)",
            'amount'       => $taxPercentage * array_sum($taxes),
            'taxable'      => $isTaxable,            
        ];

        if (empty($subscription)) {
            $invoice->invoiceItem()->create($taxes);
        } else {
            $subscription->invoiceItemDetail()->create($taxes);
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



}
