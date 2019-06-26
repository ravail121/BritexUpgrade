<?php

namespace App\Http\Controllers\Api\V1\CronJobs;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use App\Model\Addon;
use App\Model\Order;
use App\Model\Coupon;
use App\Model\Customer;
use App\Model\Plan;
use App\Model\OrderCoupon;
use App\Model\OrderGroup;
use App\Model\Subscription;
use App\Model\Invoice;
use App\Model\InvoiceItem;
use App\Model\CustomerCoupon;
use App\Model\SubscriptionCoupon;
use App\Events\InvoiceGenerated;

class RegenerateInvoiceController extends Controller
{
    const TYPES = [
        'monthly'   => 1,
        'order'     => 2
    ];

    const STATUS = [
        'open'      => 1,
        'closed'    => 2
    ];

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

    public $response;

    public function __construct()
    {
        echo "Regenerate Invoice";
    }

    public function regenerateInvoice()
    {
        $today                  = Carbon::today();
        $invoiceGroups          = Customer::customerInvoiceGroups();
        $allOpenInvocies        = [];
        $customerInvoices       = [];
        $openMonthlyInvoiceDate = 0;
        $pendingOrderInvoices   = [];

        foreach ($invoiceGroups as $invoices) {

            foreach ($invoices as $invoice) {

                $openMonthlyInvoiceDate = 0;

                $customerId = '';

                $invoiceId  = '';

                if ($invoice->type == self::TYPES['monthly'] && $invoice->status == self::STATUS['open']) {

                    $openMonthlyInvoiceDate = Carbon::parse($invoice->created_at);
                   
                    $openMonthlyInvoiceTime = $openMonthlyInvoiceDate->format('H:i');
    
                    $customerId             = $invoice->customer_id;

                    $invoiceId              = $invoice->id;

                    $openOrderInvoices      = $invoices->where('customer_id', $customerId);
                    
                    foreach ($openOrderInvoices as $orderInvoice) {
                       
                        $currentDate        = Carbon::parse($orderInvoice->created_at);

                        $currentTime        = $currentDate->format('H:i');
                        
                        if ($currentDate > $openMonthlyInvoiceDate && $invoice->id != $orderInvoice->id) {
                            
                            $pendingOrderInvoices[]   = ['invoice' => $orderInvoice, 'monthly_invoice_id' => $invoiceId];
                            
                        } elseif ($currentDate == $openMonthlyInvoiceDate && $invoice->id != $orderInvoice->id) {
                            
                            if ($currentTime > $openMonthlyInvoiceTime) {
                                
                                $pendingOrderInvoices[]   = ['invoice' => $orderInvoice, 'monthly_invoice_id' => $invoiceId];

                            } 

                        }
                        
                    }
                    
                }

            }

        }
        
        $this->processPendingOrderInvoices($pendingOrderInvoices);
        

    }

    protected function processPendingOrderInvoices($pendingOrderInvoices)
    {
        
        $subscriptionIds  = [];

        foreach ($pendingOrderInvoices as $invoice) {
            
            $invoiceItems = $invoice['invoice']->invoiceItem;

            $invoiceId    = $invoice['monthly_invoice_id'];

            foreach ($invoiceItems as $item) {
                
                if ($item->subscription_id) {
                   
                    if (!in_array($item->subscription_id, $subscriptionIds)) {

                        $subscriptionIds[] = $item->subscription_id;
                        
                    }

                }
                
            }

        }
        
        $regeneratedAmount  = $this->regenerateAmount($subscriptionIds);
        $invoiceIds         = $this->storeInvoiceItems($regeneratedAmount);

        foreach ($invoiceIds as $id) {

            $invoice            = Invoice::find($id);
            $updateInvoice      = $this->updateInvoice($invoice);
            $order              = $invoice ? Order::where('invoice_id', $invoice->id)->first() : null;
    
            if(isset($order)) {
                event(new InvoiceGenerated($order));
            }

        }


    }

    protected function updateInvoice($invoice)
    {
        // Using $invoice->invoiceItem to count coupons, only counts 1 coupon for some reason,
        // even if customer has more than one.

        if ($invoice) {

            $invoiceItems       = InvoiceItem::where('invoice_id', $invoice->id);

            $totalDiscounts     = $invoiceItems->whereIn('type', 
            [
                InvoiceItem::TYPES['coupon'],
                InvoiceItem::TYPES['manual']
            ])->sum('amount');

            $totalCharges       = $invoice->invoiceItem->whereIn('type', 
            [
                InvoiceItem::TYPES['plan_charges'],
                InvoiceItem::TYPES['feature_charges'],
                InvoiceItem::TYPES['regulatory_fee'],
                InvoiceItem::TYPES['taxes']
            ])->sum('amount');

            $subtotal           = $totalCharges - $totalDiscounts;
            $totalCredits       = app('App\Http\Controllers\Api\V1\Invoice\InvoiceController')->availableCreditsAmount($invoice->customer_id);
            
            $invoice->update(
                [
                    'subtotal'  => $subtotal,
                    'created_at'=> Carbon::now()
                ]
            );
            $totalDue           = $this->totalDue($invoice);

        }

    }

    protected function totalDue($invoice)
    {
        $totalAmount    = $invoice->subtotal;
        $paidAmount     = $invoice->creditsToInvoice->sum('amount');
        $totalDue       = $totalAmount;
        if ($paidAmount && $paidAmount < $totalAmount) {
            $totalDue = $totalAmount - $paidAmount;
        }
        $invoice->update(
            [
                'total_due'     => $totalDue
            ]
        );
        
    }

    protected function storeInvoiceItems($regeneratedAmount)
    {
        $invoiceIds = [];
        if ($regeneratedAmount['items'] && $regeneratedAmount['total']) {
            
            foreach ($regeneratedAmount['items'] as $amount) {
                
                InvoiceItem::create($amount);
                
            }


            foreach ($regeneratedAmount['total'] as $total) {
                
                $invoice                = Invoice::find($total['invoice_id']);
                $customer               = Customer::find($invoice->customer_id);
                $usedCouponsIds         = $invoice ? $invoice->invoiceItem->where('type', 6)->pluck('product_id') : null;
                $filterUniqueCouponIds  = [];

                if ($usedCouponsIds) {
                    foreach ($usedCouponsIds as $id) {
                        if (!in_array($id, $filterUniqueCouponIds) && $id) {
                            $filterUniqueCouponIds[] = $id;                  
                        }
                    }
                }

                $regenerateCoupons          = $this->regenerateCoupons($filterUniqueCouponIds, $invoice);

                if ($regenerateCoupons) {
                    $accountCoupons         = app('App\Http\Controllers\Api\V1\CronJobs\MonthlyInvoiceController')->customerAccountCoupons($customer, $invoice);
                    $subscriptionCoupons    = app('App\Http\Controllers\Api\V1\CronJobs\MonthlyInvoiceController')->customerSubscriptionCoupons($invoice, $customer->billableSubscriptions);
                }
                if (!in_array($invoice->id, $invoiceIds)) {
                    $invoiceIds[] = $invoice->id;
                }

            }
                
                return $invoiceIds;

        }
        
    }

    protected function regenerateAmount($subscriptionIds)
    {

        $invoiceItems   = [];

        $total          = [];

        foreach ($subscriptionIds as $id) {

            $subscription   = Subscription::find($id);

            $order          = Order::find($subscription->order_id);

            $customer       = Customer::find($order->customer_id);

            $invoice        = $customer->invoice->where('type', 1)->where('status', 1)->first();

            $startDate      = Carbon::parse($customer->billing_start);

            $endDate        = Carbon::parse($customer->billing_end);

            $totalDays      = $startDate->diffInDays($endDate);

            $remainingDays  = Carbon::today()->diffInDays($endDate);

            $plan           = Plan::find($subscription->plan_id);

            $planCharges    = $plan->amount_recurring;

            $total[]        = ['invoice_id' => $invoice->id, 'amount' => $planCharges];          

            //Plan Charges and tax
            $invoiceItems[] = $this->itemAmount($invoice, $subscription, $plan, self::SUBSCRIPTION_TYPES['plan']);

            if ($plan->taxable == 1) {

                $rate           = $customer->stateTax->rate * $planCharges / 100;

                $invoiceItems[] = $this->taxesAndFeesAmount($invoice, $subscription, $rate, self::FEE_TYPES['taxes'], self::FEE_DESCRIPTION['taxes']);

                $total[]        = ['invoice_id' => $invoice->id, 'amount' => $rate];
            }


            //Regulatory Fee
            $regulatoryFee      = $plan->regulatory_fee_type == 2 ? $planCharges * $plan->regulatory_fee_amount / 100 : $plan->regulatory_fee_amount;

            $total[]            = ['invoice_id' => $invoice->id, 'amount' => $regulatoryFee];
            
            $invoiceItems[]     = $this->taxesAndFeesAmount($invoice, $subscription, $regulatoryFee, self::FEE_TYPES['regulatory'], self::FEE_DESCRIPTION['regulatory']);


            //Addons Charges and tax
            if (isset($order->orderGroup->order_group_addon)) {

                if (count($order->orderGroup->order_group_addon)) {

                    $addonsGroup = $order->orderGroup->order_group_addon;

                    foreach ($addonsGroup as $addon) {

                        $addon          = Addon::find($addon->addon_id);

                        $addonCharges   = $addon->amount_recurring;

                        $total[]        = ['invoice_id' => $invoice->id, 'amount' => $addonCharges];

                        $invoiceItems[] = $this->itemAmount($invoice, $subscription, $addon, self::SUBSCRIPTION_TYPES['addon']);

                        if ($addon->taxable == 1) {

                            $rate           = $customer->stateTax->rate * $addonCharges / 100;

                            $invoiceItems[] = $this->taxesAndFeesAmount($invoice, $subscription, $rate, self::FEE_TYPES['taxes'], self::FEE_DESCRIPTION['taxes']);

                            $total[]        = ['invoice_id' => $invoice->id, 'amount' => $rate];

                        }
                        
                    }

                }

            }

        }
        
        return ['items' => $invoiceItems, 'total' => $total];
        
    }

    protected function regenerateCoupons($couponIds, $invoice)
    {
        foreach ($couponIds as $id) {

            $customerCoupon                 = CustomerCoupon::where('coupon_id', $id)->first();
            $cyclesRemainingAccount         = $customerCoupon ? $customerCoupon->cycles_remaining : null;

            $subscriptionCoupon             = SubscriptionCoupon::where('coupon_id', $id)->first();
            $cyclesRemainingSubscription    = $subscriptionCoupon ? $subscriptionCoupon->cycles_remaining : null;

            if ($customerCoupon) {
                $customerCoupon->update(
                    [
                        'cycles_remaining' => $cyclesRemainingAccount + 1
                    ]
                );
            } elseif ($subscriptionCoupon) {
                $subscriptionCoupon->update(
                    [
                        'cycles_remaining' => $cyclesRemainingSubscription + 1
                    ]
                );
            }
        }
        if ($invoice->invoiceItem()->where('type', 6)->delete()) {
            return true;
        };
    }

    protected function itemAmount($invoice, $subscription, $item, $type)
    {
        return [
            'invoice_id'        => $invoice->id,
            'subscription_id'   => $subscription->id,
            'product_type'      => $type['product_type'],
            'product_id'        => $item->id,
            'type'              => $type['type'],
            'description'       => $type['description'].$item->description,
            'amount'            => $item->amount_recurring,
            'start_date'        => Carbon::today()->toDateString(),
            'taxable'           => $item->taxable
        ];
    }

    protected function taxesAndFeesAmount($invoice, $subscription, $amount, $type, $description)
    {
        return [
            'invoice_id'        => $invoice->id,
            'subscription_id'   => $subscription->id,
            'product_type'      => '',
            'product_id'        => null,
            'type'              => $type,
            'description'       => $description,
            'amount'            => $amount,
            'start_date'        => Carbon::today()->toDateString(),
            'taxable'           => 0
        ];
    }
}
