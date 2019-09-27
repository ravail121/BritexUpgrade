<?php

namespace App\Http\Controllers\Api\V1\CronJobs;

use PDF;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use App\Model\Customer;
use App\Model\Subscription;
use App\Model\InvoiceItem;
use App\Model\CustomerCoupon;
use App\Model\SubscriptionCoupon;
use App\Http\Controllers\Api\V1\Traits\InvoiceTrait;
use App\Http\Controllers\Api\V1\Traits\InvoiceCouponTrait;
use Illuminate\Http\Request;
use App\Model\SystemGlobalSetting;
use App\Events\InvoiceGenerated;

class RegenerateInvoiceController extends Controller
{

    use InvoiceTrait, InvoiceCouponTrait;

    const FEE_TYPES = [
        'regulatory' => 5,
        'taxes'      => 7
    ];

    const FEE_DESCRIPTION = [
        'regulatory'    => '(Regulatory Fee) - Regulatory',
        'taxes'         => '(Taxes)'
    ];

    const SUBSCRIPTION_TYPES = [

        'plan'      => [
            'type'          => 1,
            'description'   => '(Billable Subscription) ',
            'product_type'  => 'plan'
        ],

        'addon'     => [
            'type'          => 2,
            'description'   => '(Billable Addon) ',
            'product_type'  => 'addon'
        ]

    ];

    public function __construct()
    {
        echo "Regenerate Invoice <br>";
    }

    public function regenerateInvoice(Request $request)
    {
        $customers = Customer::invoicesForRegeneration();
        foreach ($customers as $customer) {
            $invoices = $customer->openAndUnpaidInvoices;
            foreach ($invoices as $invoice) {
                $invoiceAfterMonthly = $customer->orderInvoice
                    ->filter(function($newInvoice, $i){
                        return $newInvoice->order->count();
                    });
                $invoiceAfterMonthly = $customer->compareDates($invoice, $invoiceAfterMonthly);
                $amount = count($invoiceAfterMonthly) ? $this->processPendingOrderInvoices($invoiceAfterMonthly, $customer, $invoice) : null;
                count($invoiceAfterMonthly) && $amount ? $this->updateInvoice($invoice) : null;
                $request->headers->set('authorization', $customer->company->api_key);
                count($invoiceAfterMonthly) && isset($invoice->order) && $invoice->order->count() && $amount ? $this->saveAndSendInvoice($invoice->order) : null;
            }
        }
    }

    protected function processPendingOrderInvoices($pendingOrderInvoices, $customer, $invoice)
    {
        foreach ($pendingOrderInvoices as $pendingInvoice) {
            $subscriptionIds = array_unique(
                $pendingInvoice->invoiceItem
                    ->pluck('subscription_id')
                    ->filter(function($id, $index) {
                        return $id != 0 && $id != null;
                    })->toArray()
                );
        }
        return count($subscriptionIds) ? $this->regenerateAmount($subscriptionIds, $customer, $invoice) : false;
        
    }

    protected function saveAndSendInvoice($order)
    {
        $path = SystemGlobalSetting::first()->upload_path;
        $fileSavePath   = $path.'/uploads/'.$order->company_id.'/invoice-pdf/';
        $subscriptions  = $this->subscriptionData($order);
        $data           = $this->dataForInvoice($order);
        if (!$subscriptions) {
            return 'Api error: missing subscriptions data';
        }
        $generatePdf = PDF::loadView('templates/monthly-invoice', compact('data', 'subscriptions'))->setPaper('letter', 'portrait');
        try {
            if (file_exists($fileSavePath.$order->hash.'.pdf')) {
                @unlink($fileSavePath.$order->hash.'.pdf');
            }
            $generatePdf->save($fileSavePath.$order->hash.'.pdf');
            event(new InvoiceGenerated($order, $generatePdf));
        } catch (Exception $e) {
            \Log::info('Pdf Save Error: '.$e->getMessage());
        }
    }

    protected function regenerateAmount($subscriptionIds, $customer, $invoice)
    {
        $taxOnAmount = [0];
        $count = 0;
        foreach ($subscriptionIds as $id) {
            $subscription = Subscription::find($id);
            $plan = $subscription->plan;
            if ($subscription->phone_number) {
                $count++;
                // Insert Plan and get amount for tax if plan.taxable = 1
                $this->itemAmount($invoice, $subscription, $plan, self::SUBSCRIPTION_TYPES['plan']);
                $taxOnAmount[] = $plan->taxable ? $plan->amount_recurring : 0;

                // Insert Regulatory fee depending on type.
                $regulatoryAmount = $plan->regulatory_fee_type == 1 ? $plan->regulatory_fee_amount : $plan->regulatory_fee_amount * $plan->amount_recurring / 100;
                $plan->regulatory_fee_amount ? $this->taxesAndFeesAmount($invoice, $subscription, $regulatoryAmount, self::FEE_TYPES['regulatory'], self::FEE_DESCRIPTION['regulatory']) : null;

                // Insert Addons with tax if taxable.
                $subscriptionAddons = $subscription->subscriptionAddon;
                if (isset($subscriptionAddons) && $subscriptionAddons->count()) {
                    foreach ($subscriptionAddons as $subAddon) {
                        $addon = $subAddon->addons;
                        $this->itemAmount($invoice, $subscription, $addon, self::SUBSCRIPTION_TYPES['addon']);
                        $taxOnAmount[] = $addon->taxable ? $addon->amount_recurring : 0;
                    }
                }

                // If tax != 0, insert tax.
                $taxRate = array_sum($taxOnAmount) ? array_sum($taxOnAmount) * $customer->tax->rate / 100 : 0;
                $taxRate ? $this->taxesAndFeesAmount($invoice, $subscription, $taxRate, self::FEE_TYPES['taxes'], self::FEE_DESCRIPTION['taxes']) : null;               
            }
        }
        if ($count) {
            $this->regenerateCoupons($invoice, $customer);
            return true;
        }
        return false;
    }

    protected function regenerateCoupons($invoice, $customer)
    {
        $usedCoupons = $invoice->couponUsed;
        $subscriptionCoupons = $usedCoupons->where('subscription_id', '!=', 0)->pluck('product_id', 'subscription_id');
        $customerCoupons = array_unique($usedCoupons->where('subscription_id', 0)->pluck('product_id')->toArray());
        foreach ($subscriptionCoupons as $subscription => $coupon) {
            if ($subscription) {
                if ($coupon) {
                    SubscriptionCoupon::where([
                        ['subscription_id', $subscription],
                        ['coupon_id', $coupon],
                        ['cycles_remaining', '!=', -1]])->increment('cycles_remaining');
                }
            }
        }
        foreach ($customerCoupons as $coupon) {
            $finiteCoupons = CustomerCoupon::where([['customer_id', $customer->id], ['coupon_id', $coupon]])->filter(function ($c) { return $c->cycles_remaining > -1; });
            
            $finiteCoupons->increment('cycles_remaining');
        }
        foreach ($usedCoupons as $coupon) { $coupon->delete(); }
        $this->customerAccountCoupons($customer, $invoice);
        $this->customerSubscriptionCoupons($invoice, $customer->billableSubscriptions);
    }

    protected function itemAmount($invoice, $subscription, $item, $type)
    {
        InvoiceItem::create([
            'invoice_id'        => $invoice->id,
            'subscription_id'   => $subscription->id,
            'product_type'      => $type['product_type'],
            'product_id'        => $item->id,
            'type'              => $type['type'],
            'description'       => $type['description'].$item->description,
            'amount'            => $item->amount_recurring,
            'start_date'        => Carbon::today()->toDateString(),
            'taxable'           => $item->taxable
        ]);
    }

    protected function taxesAndFeesAmount($invoice, $subscription, $amount, $type, $description)
    {
        InvoiceItem::create([
            'invoice_id'        => $invoice->id,
            'subscription_id'   => $subscription->id,
            'product_type'      => '',
            'product_id'        => null,
            'type'              => $type,
            'description'       => $description,
            'amount'            => $amount,
            'start_date'        => Carbon::today()->toDateString(),
            'taxable'           => 0
        ]);
    }

    protected function updateInvoice($invoice)
    {
        $this->availableCreditsAmount($invoice->customer_id);
        $total = $invoice->cal_total_charges;
        $invoice->update([
                'subtotal' => $total,
                'total_due' => $total - $invoice->creditsToInvoice->sum('amount'),
                'created_at' => Carbon::now()
            ]);
    }

}
