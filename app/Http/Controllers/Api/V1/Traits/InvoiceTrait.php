<?php

namespace App\Http\Controllers\Api\V1\Traits;

use PDF;
use Exception;
use App\Model\Order;
use App\Model\Plan;
use App\Model\Addon;
use App\Model\Device;
use App\Model\Sim;
use App\Model\Invoice;
use App\Model\InvoiceItem;
use App\Model\Subscription;
use App\Events\InvoiceGenerated;
use App\Events\UpgradeDowngradeInvoice;
use Carbon\Carbon;
use App\Model\SystemGlobalSetting;
use App\Model\PendingCharge;
use App\Model\Customer;
use App\Model\CreditToInvoice;

trait InvoiceTrait
{
    
    public function addRegulatorFeesToSubscription($subscription, $invoice, $isTaxable, $order = null, $autoGeneratedOrder = null)
    {
        $amount = 0;
        
        $plan   = $subscription->plan;
        
        if ($plan->regulatory_fee_type == Plan::REGULATORY_FEE_TYPES['fixed_amount']) {
            $amount = $plan->regulatory_fee_amount;

        } elseif ($plan->regulatory_fee_type == Plan::REGULATORY_FEE_TYPES['percentage_of_plan_cost']) {
            
            if($autoGeneratedOrder == 1){
                $planAmount = $plan->amount_recurring;
            }else if($subscription->upgrade_downgrade_status == null){
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
            'amount'       => number_format($amount, 2),
            'taxable'      => $isTaxable,
        ]);
    }
    

    public function addTaxesToSubscription($subscription, $invoice, $isTaxable)
    {
        
        $taxPercentage = isset($invoice->customer->stateTax->rate) ? $invoice->customer->stateTax->rate / 100 : 0;

        if ($taxPercentage > 0) {
            $taxableItems = $subscription->invoiceItemDetail->where('taxable', 1);
            $taxesWithSubscriptions = $taxableItems->sum('amount');
            // $taxesForActivationFee  = $taxableItems->where('type', InvoiceItem::INVOICE_ITEM_TYPES['plan_charges']);
            if ($taxesWithSubscriptions > 0) {
                $subscription->invoiceItemDetail()->create(
                    [
                        'invoice_id'   => $invoice->id,
                        'product_type' => '',
                        'product_id'   => null,
                        'type'         => InvoiceItem::INVOICE_ITEM_TYPES['taxes'],
                        'start_date'   => $invoice->start_date,
                        'description'  => "(Taxes)",
                        'amount'       => number_format($taxPercentage * $taxesWithSubscriptions, 2),
                        'taxable'      => $isTaxable,            
                    ]
                );
            }
        }
    }

    public function addTaxesToStandalone($orderId, $isTaxable, $item)
    {
        $invoice = Order::find($orderId)->invoice;
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
                        'amount'       => number_format($taxPercentage * $taxesWithoutSubscriptions, 2),
                        'taxable'      => $isTaxable,    
                    ]
                );
            }  
        }
    }

    public function addActivationCharges($subscription, $invoice, $description)
    {
        $plan = $subscription->plan;
        $activationFee = $plan->amount_onetime;
        if ($activationFee > 0) {
            $subscription->invoiceItemDetail()->create([
                'invoice_id'   => $invoice->id,
                'product_type' => '',
                'product_id'   => null,
                'type'         => InvoiceItem::INVOICE_ITEM_TYPES['one_time_charges'],
                'start_date'   => $invoice->start_date,
                'description'  => $description,
                'amount'       => $activationFee,
                'taxable'      => $plan->taxable, 
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
                    'amount'       => number_format($taxPercentage * $taxesWithoutSubscriptions, 2),
                    'taxable'      => $isTaxable,            
                ]
            );
        }
    }

    public function generateInvoice($order, $fileSavePath, $request = null)
    {
        $request ? $request->headers->set('authorization', $order->company->api_key) : null;
        $order = Order::find($order->id);
        if ($order && $order->invoice && $order->invoice->invoiceItem) {
            $data = $this->dataForInvoice($order);
            
            if ($order->invoice->type == Invoice::TYPES['one-time']) {
                $ifUpgradeOrDowngradeInvoice = $this->ifUpgradeOrDowngradeInvoice($order);
                if ($ifUpgradeOrDowngradeInvoice['upgrade_downgrade_status']) {
                    $generatePdf = PDF::loadView('templates/onetime-invoice', compact('data', 'ifUpgradeOrDowngradeInvoice'));
                    event(new UpgradeDowngradeInvoice($order, $generatePdf));
                    $this->saveInvoiceFile($generatePdf, $fileSavePath.$order->hash);
                    return $generatePdf->download('Invoice.pdf');
                } else {    
                    $generatePdf = PDF::loadView('templates/onetime-invoice', compact('data'));
                }
            } else {   
                $subscriptions = $this->subscriptionData($order);
                if (!$subscriptions) {
                    return 'Api error: missing subscriptions data';
                }
                $generatePdf = PDF::loadView('templates/monthly-invoice', compact('data', 'subscriptions'))->setPaper('letter', 'portrait');                        
            }

            $this->saveInvoiceFile($generatePdf, $fileSavePath.$order->hash); // To save the generated pdf

            $request ? event(new InvoiceGenerated($order, $generatePdf)) : null; // To send the generated pdf via email

            return $generatePdf->download('Invoice.pdf'); //To trigger the old generate and download logic

        } else {

            return 'Sorry, we could not find the details for your invoice';

        }

        
        return 'Sorry, something went wrong please try again later......';
    }

    public function saveInvoiceFile($generatePdf, $fileSavePath)
    {
        
        try {
            if (!file_exists($fileSavePath.'.pdf')) {
                
                $generatePdf->save($fileSavePath.'.pdf');
            }
        } catch (Exception $e) {
            \Log::info('Pdf Save Error: '.$e->getMessage());
        }
    }

    public function dataForInvoice($order)
    {                                    

        $invoice = [
            'order'                         =>   $order,
            'invoice'                       =>   $order->invoice,
                                                 //Had to use this because $order->invoice->invoiceItem is excluding shipping fee.
            'standalone_items'              =>   Invoice::find($order->invoice_id)->invoiceItem->where('subscription_id', null),
            'previous_bill'                 =>   $this->previousBill($order)
        ];
        return $invoice;
    }

    public function subscriptionData($order)
    {
        $subscriptionIds = $order->invoice->invoiceItem->pluck('subscription_id')->toArray();
        $subscriptions   = [];

        foreach (array_unique($subscriptionIds) as $id) {
            $subscriptionsExists = Subscription::find($id);
            $subscriptionsExists ? array_push($subscriptions, Subscription::find($id)) : null;
        }
        
        if (!count($subscriptions)) { return false; }
        return $subscriptions;
    }


    public function previousBill($order)
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

    public function ifUpgradeOrDowngradeInvoice($order)
    {
        $subscriptionId = array_unique($order->invoice->invoiceItem->pluck('subscription_id')->toArray());
        $subscription = Subscription::find($order->allOrderGroup->first()->subscription_id);
        if (count($subscriptionId) == 1) {
            $subscription = Subscription::find($subscriptionId[0]);
            if ($subscription && $subscription->upgrade_downgrade_status) {
                $addonsIds = $order->invoice->invoiceItem->where('type', InvoiceItem::TYPES['feature_charges'])->pluck('product_id');
                $planData = [
                    'name' => Plan::find($subscription->plan_id)->name,
                    'amount' => $order->invoice->cal_plan_only_charges,
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
                    'total' => $order->invoice->cal_plan_charges,
                    'phone' => $subscription->phone_number,
                    'total_line' => $order->invoice->cal_total_charges,
                    'upgrade_downgrade_status' => true
                ];
            } else {
                return [
                    'upgrade_downgrade_status' => false
                ];
            }
        }
    }

    public function ifTotalDue($order)
    {
        $totalAmount    = $order->invoice->cal_total_charges;
        $paidAmount     = $order->invoice->creditsToInvoice->sum('amount');
        $totalDue       = $totalAmount > $paidAmount ? $totalAmount - $paidAmount : 0;
        if ($totalDue > 0) {
            $order->invoice->update([
                    'total_due'  => str_replace(',', '',number_format($totalDue, 2)),
                    'status' => 1 
                ]);
            PendingCharge::create([
                'customer_id' => $order->customer_id,
                'subscription_id' => 0,
                'invoice_id' => $order->invoice_id,
                'type'  => 3,
                'amount' => str_replace(',', '',number_format($totalDue)),
                'description' => 'Pending one time payment'
            ]);
        }
  
    }

    public function addShippingCharges($orderId)
    {

        $order = Order::find($orderId);
        
        $items = $order->invoice->invoiceItem;

        $itemWithShippingCharges  = [];

        foreach ($items as $item) {

            if ($item->product_type == InvoiceItem::PRODUCT_TYPE['device'] && $item->product_id) {

                $shippingFee        = Device::find($item->product_id)->shipping_fee;

                if ($shippingFee) { $itemWithShippingCharges[] = [
                    'amount'            => $shippingFee, 
                    'subscription_id'   => $item->subscription_id, 
                    'taxable'           => 0,
                    'invoice_id'        => $item->invoice_id,
                    'start_date'        => Carbon::today()
                ]; }

            } elseif ($item->product_type == InvoiceItem::PRODUCT_TYPE['sim']) {

                $shippingFee        = Sim::find($item->product_id)->shipping_fee;

                if ($shippingFee) { $itemWithShippingCharges[] = [
                    'amount'            => $shippingFee, 
                    'subscription_id'   => $item->subscription_id, 
                    'taxable'           => 0, 
                    'invoice_id'        => $item->invoice_id,
                    'start_date'        => Carbon::today()
                ]; }

            }

        }

        $defaultValuesToInsert = [
            'product_type' => '',
            'type'         => InvoiceItem::TYPES['one_time_charges'],
            'description'  => 'Shipping Fee',
        ];

        foreach ($itemWithShippingCharges as $items) {
            
            InvoiceItem::create(array_merge($items,$defaultValuesToInsert));

        }
        
        return true;
    }

    public function regenerateRefundInvoice($encryptedId)
    {
        $decryptedId = pack("H*",$encryptedId);
        $invoiceId   = substr($decryptedId, strpos($decryptedId, "=") + 1);
        $invoice = Invoice::where('id', $invoiceId)->with('customer', 'invoiceItem')->first();

        $company = $invoice->customer->company_id;
        $path = SystemGlobalSetting::first()->upload_path;
        $fileSavePath = $path.'/uploads/'.$company.'/non-order-invoice-pdf/'.$encryptedId;
        $pdf = PDF::loadView('templates/custom-charge-invoice', compact('invoice'));
        $this->saveInvoiceFile($pdf, $fileSavePath);
    }

    public function availableCreditsAmount($id)
    {
        $customer = Customer::find($id);

        $credits  = $customer->creditsNotAppliedCompletely;
        
        foreach ($credits as $credit) {

            $availableCredits[] = ['id' => $credit->id, 'amount' => $credit->amount];
            
        }
        
        if (isset($availableCredits)) {

            foreach ($availableCredits as $key => $credit) {

                $notFullUsedCredit = CreditToInvoice::where('credit_id', $credit['id'])->sum('amount');

                if ($notFullUsedCredit && $notFullUsedCredit < $credit['amount']) {

                    $totalUsableCredits = $credit['amount'] - $notFullUsedCredit;
                    
                    $openInvoices = $customer->invoice->where('status', Invoice::INVOICESTATUS['open']);
                    $this->applyCreditsToInvoice($credit['id'], $totalUsableCredits, $openInvoices);
                    

                } else if (!$notFullUsedCredit) {

                    $totalUsableCredits = $credit['amount'];
                    $openInvoices = $customer->invoice->where('status', Invoice::INVOICESTATUS['open']);
                    $this->applyCreditsToInvoice($credit['id'], $totalUsableCredits, $openInvoices);

                } 
                

                
            }
        }
        
    }
    

}