<?php

namespace App\Http\Controllers\Api\V1\Invoice;

use PDF;
use Validator;
use Carbon\Carbon;
use App\Model\Tax;
use App\Model\Sim;
use App\Model\Plan;
use App\Model\Order;
use App\Model\Addon;
use App\Model\Device;
use App\Model\Coupon;
use App\Model\Company;
use App\Model\Invoice;
use App\Model\Customer;
use App\Model\Credit;
use App\Model\InvoiceItem;
use App\Model\OrderGroup;
use App\Model\OrderGroupAddon;
use App\Model\Subscription;
use App\Model\PendingCharge;
use App\Model\CustomerCoupon;
use App\Model\SubscriptionAddon;
use App\Model\SubscriptionCoupon;
use App\Model\CustomerStandaloneSim;
use App\Model\CustomerStandaloneDevice;
use App\Http\Controllers\Api\V1\CronJobs\InvoiceTrait;
use App\libs\Constants\ConstantInterface;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Events\InvoiceGenerated;
use Illuminate\Support\Collection;
use App\Http\Controllers\Controller;
use App\Http\Controllers\BaseController;
use App\Classes\GenerateMonthlyInvoiceClass;
use App\Model\CreditToInvoice;

class InvoiceController extends BaseController implements ConstantInterface
{
    use InvoiceTrait;

    const DEFAULT_INT = 1;
    const DEFAULT_ID  = 0;
    const SIM_TYPE    = 'sim';
    const PLAN_TYPE   = 'plan';
    const ADDON_TYPE  = 'addon';
    const DEVICE_TYPE = 'device';
    const DESCRIPTION = 'Activation Fee';
    const SHIPPING    = 'Shipping Fee';
    const ONETIME     = 3;
    const TAXES       = 7;
    const COUPONS     = 6;

    /**
     * Date-Time variable
     * 
     * @var $carbon
     */
    public $carbon;


    public $input;



    /**
     * Sets current date variable
     * 
     * @param Carbon $carbon
     */
    public function __construct(Carbon $carbon)
    {
        $this->carbon = $carbon;
        $this->input  = [];
    }





    // /**
    //  * []
    //  *  
    //  * @param  Request $request
    //  * @return void
    //  */
    // public function startBilling(Request $request)
    // {
    //     $customer = null;

    //     return $this->respond($customer);
    // }





    /**
     * Creates invoice_item and sends email with invoice.pdf attachment
     * 
     * @param  Request    $request
     * @return Response
     */
    public function oneTimeInvoice(Request $request)
    {
        $msg = '';
        if ($request->data_to_invoice) {
            $invoice = $request->data_to_invoice;

            if (isset($invoice['subscription_id'])) {


                $subscription = Subscription::find($invoice['subscription_id'][0]);
                $order        = $this->updateCustomerDates($subscription);

                $invoiceItem  = $this->subscriptionInvoiceItem($invoice['subscription_id']);

                $msg = (!$invoiceItem) ? 'Subscription Invoice item was not generated' : 'Invoice item generated successfully.'; 
                
            }
            if (isset($invoice['customer_standalone_device_id'])) {

                $orderId = CustomerStandaloneDevice::find($invoice['customer_standalone_device_id'])->first()->order_id;

                $standaloneDevice = CustomerStandaloneDevice::find($invoice['customer_standalone_device_id'][0]);

                $order = $this->updateCustomerDates($standaloneDevice);
                        
                $invoiceItem = $this->standaloneDeviceInvoiceItem($invoice['customer_standalone_device_id']);

                foreach ($invoice['customer_standalone_device_id'] as $id) {
                    $shippingAmount   = CustomerStandaloneDevice::find($id)->device->shipping_fee;
                   $this->addShippingChargesStandalone($shippingAmount, $orderId);
                }

                $taxes = $this->addTaxes([], Order::find($orderId)->invoice, 0);

                $msg = (!$invoiceItem) ? 'Standalone Device Invoice item was not generated' : 'Invoice item generated successfully.' ;


            }

            
            if (isset($invoice['customer_standalone_sim_id'])) {

                $orderId = CustomerStandaloneSim::find($invoice['customer_standalone_sim_id'])->first()->order_id;

                $standaloneSim = CustomerStandaloneSim::find($invoice['customer_standalone_sim_id'][0]);

                $order = $this->updateCustomerDates($standaloneSim);
                        
                $invoiceItem = $this->standaloneSimInvoiceItem($invoice['customer_standalone_sim_id']);

                foreach ($invoice['customer_standalone_sim_id'] as $id) {
                    $shippingAmount   = CustomerStandaloneSim::find($id)->first()->sim->shipping_fee;
                    $this->addShippingChargesStandalone($shippingAmount, $orderId);
                }

                $taxes = $this->addTaxes([], Order::find($orderId)->invoice, 0);

                $msg = (!$invoiceItem) ? 'Standalone Sim Invoice item was not generated' : 'Invoice item generated successfully.';

            }
        }

        
    
        if ($request->customer_id) {
            $this->availableCreditsAmount($request->customer_id);
        }

        if ($request->hash) {
            $currentInvoice =  Order::where('hash', $request->hash)->first()->invoice;
            $this->addTaxesToSubtotal($currentInvoice);
        }

        $this->addShippingCharges(Order::where('hash', $request->hash)->first(), InvoiceItem::TYPES['one_time_charges']);
       

        if($order) {
            event(new InvoiceGenerated($order));
        }

        return $this->respond($msg);
    }

    public function getTax(Request $request)
    {
        $taxes      = Order::where('hash', $request->hash)->first()->invoice->invoiceItem->where('type', self::TAXES)->sum('amount');
        $shipping   = Order::where('hash', $request->hash)->first()->invoice->invoiceItem->where('description', self::SHIPPING)->sum('amount');
        return ['taxes' => $taxes, 'shipping' => $shipping];
    }

    /**
     * Generates the Invoice template and downloads the invoice.pdf file
     * 
     * @param  Request    $request
     * @return Response
     */
    public function get(Request $request)
    {
        $order = Order::hash($request->order_hash)->first();

        if ($order) {

            $proratedAmount = !isset($order->orderGroup->plan_prorated_amt) ? 0 : $order->orderGroup->plan_prorated_amt;

            $data = $order->isOrder($order) ? $this->setOrderInvoiceData($order) : $this->setMonthlyInvoiceData($order);
            
            
            $regulatoryFee          = $order->invoice->cal_regulatory;
            $stateTax               = $order->invoice->cal_stateTax;
            $taxes                  = $order->invoice->cal_taxes;
            $credits                = $order->invoice->cal_credits;
            $totalCharges           = $order->invoice->cal_total_charges;
            $oneTimeCharges         = $order->invoice->cal_onetime;
            $usageCharges           = $order->invoice->cal_usage_charges;
            $planCharges            = $order->invoice->cal_plan_charges;
            $totalAccountCharges    = $order->invoice->invoiceItem->whereIn('type',
                                        [
                                            InvoiceItem::TYPES['one_time_charges'],
                                            InvoiceItem::TYPES['regulatory_fee'],
                                            InvoiceItem::TYPES['taxes'],
                                        ]
                                    )->sum('amount');
            $serviceChargesProrated = $planCharges + $oneTimeCharges + $usageCharges;
            $serviceCharges         = $proratedAmount == null ? $order->invoice->cal_service_charges : $serviceChargesProrated;
            $shippingFee            = $order->invoice->invoiceItem->where('description', 'Shipping Fee')->sum('amount');
            $taxAndShipping         = $taxes + $shippingFee;
            $totalCredits           = $order->credits->sum('amount');
            $oldUsedCredits         = $order->credits->first() ? $order->invoice->creditsToInvoice->where('credit_id', '!=', $order->credits->first()->id)->sum('amount') : $order->invoice->creditsToInvoice->sum('amount');
            $totalCreditsToInvoice  = $order->invoice->creditsToInvoice->sum('amount');
            $totalCoupons           = $order->invoice->invoiceItem->where('type', self::COUPONS)->sum('amount');
            $accountChargesDiscount = $totalAccountCharges - $totalCoupons - $shippingFee;
            $totalLineCharges       = $planCharges + $oneTimeCharges + $taxes + $usageCharges - $totalCoupons;

            $invoice = [
                'service_charges'           => self::formatNumber($serviceCharges),
                'taxes'                     => self::formatNumber($taxes),
                'credits'                   => self::formatNumber($credits),
                'total_charges'             => self::formatNumber($totalAccountCharges),
                'total_one_time_charges'    => self::formatNumber($oneTimeCharges),
                'total_usage_charges'       => self::formatNumber($usageCharges),
                'plan_charges'              => self::formatNumber($planCharges),
                'serviceChargesProrated'    => self::formatNumber($serviceChargesProrated),
                'regulatory_fee'            => self::formatNumber($regulatoryFee),
                'state_tax'                 => self::formatNumber($stateTax),
                'total_account_charges'     => self::formatNumber($oneTimeCharges + $taxes),
                'subtotal'                  => self::formatNumber($order->invoice->subtotal),
                'shipping_fee'              => self::formatNumber($shippingFee),
                'plans'                     => $this->plans($order),
                'addons'                    => $this->addons($order),
                'tax_and_shipping'          => self::formatNumber($taxAndShipping),
                'standalone_data'           => $this->setStandaloneItemData($order),
                'total_old_credits'         => self::formatNumber($oldUsedCredits),
                'total_credits_to_invoice'  => self::formatNumber($totalCreditsToInvoice),
                'total_payment'             => self::formatNumber($order->credits->sum('amount')),
                'total_used_credits'        => self::formatNumber($totalCredits + $oldUsedCredits),
                'date_payment'              => $order->credits->first() ? $order->credits->first()->date : '',
                'date_credit'               => $order->invoice->creditsToInvoice->first() ? $order->invoice->creditsToInvoice->first()->created_at->format('y/m/d') : '',
                'credits_and_coupons'       => self::formatNumber($totalCreditsToInvoice + $totalCoupons),
                'total_coupons'             => self::formatNumber($totalCoupons),
                'account_charges_discount'  => self::formatNumber($accountChargesDiscount),
                'total_line_charges'        => self::formatNumber($totalLineCharges)
                
            ];

            $invoice = array_merge($data, $invoice);
 
            if ($order->invoice->type == Invoice::TYPES['one-time']) {
                $pdf = PDF::loadView('templates/onetime-invoice', compact('invoice'))->setPaper('letter', 'portrait');
                return $pdf->download('invoice.pdf');
            
            } else {
                $pdf = PDF::loadView('templates/monthly-invoice', compact('invoice'))->setPaper('letter', 'portrait');                    
                return $pdf->download('invoice.pdf');
                
            }

        }
        return 'Sorry, something went wrong please try again later......';
        
    }

    protected function plans($order)
    {
        $allPlans = $order->invoice->invoiceItem
            ->where('type', InvoiceItem::TYPES['plan_charges']);
        
        foreach ($allPlans as $plan) {
            $plans[]    = ['name' => Plan::find($plan->product_id)->name, 'amount' => $plan->amount];
        }

        return !empty($plans) ? $plans : $plans = [['name' => 'No plans', 'amount' => 0]];
    }

    protected function addons($order)
    {
        $allAddonIds = $order->invoice->invoiceItem
            ->where('type', InvoiceItem::TYPES['feature_charges']);
  
        foreach ($allAddonIds as $addon) {
            $addons[]   = ['name' => Addon::find($addon->product_id)->name, 'amount' => $addon->amount];
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
            //$oldUsedCredits = 0;
        }
        //$oldCreditToInvoice = $order->credits->where('type', 2)->first();
        //$oldUsedCredits = isset($oldCreditToInvoice) ? $oldCreditToInvoice->usedOnInvoices->sum('amount') : 0;
        $arr = [
            'invoice_num'           => $order->invoice->id,
            'subscriptions'         => [],
            'start_date'            => $order->invoice->start_date,
            'end_date'              => $order->invoice->end_date,
            'due_date'              => $order->invoice->due_date,
            'total_due'             => $order->invoice->total_due,
            'subtotal'              => $order->invoice->subtotal,
            'invoice_item'          => $order->invoice->invoiceItem,
            'today_date'            => $this->carbon->toFormattedDateString(),
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
            'start_date'            => $order->invoice->start_date,
            'end_date'              => $order->invoice->end_date,
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
        
        $standalone = [
            'standalone_onetime_charges'  => self::formatNumber($deviceAndSim),
            'standalone_shipping_fee'     => self::formatNumber($shipping),
            'taxes'                       => self::formatNumber($tax + $shipping),
            'total'                       => self::formatNumber($tax + $shipping + $deviceAndSim)
        ];
        
        return $standalone;
    }

    protected function setOrderInvoiceData($order)
    {
        $arr = $this->invoiceData($order);

        foreach ($order->subscriptions as $subscription) {
            $planCharges    = $subscription->cal_plan_charges;
            $onetimeCharges = $subscription->cal_onetime_charges;
            $usageCharges   = $subscription->cal_usage_charges;
            $tax            = $subscription->cal_taxes;
            $total          = $order->invoice->invoiceItem->where('subscription_id', $subscription->id)->sum('amount');
            $shippingFee    = $order->invoice->invoiceItem
                                            ->where('subscription_id', $subscription->id)
                                            ->where( 'description', 'Shipping Fee')->sum('amount');
            $subscriptionData = [
                'subscription_id' => $subscription->id,
                'plan_charges'    => self::formatNumber($planCharges),
                'onetime_charges' => self::formatNumber($onetimeCharges),
                'phone'           => $subscription->phone_number,
                'usage_charges'   => $usageCharges,
                'tax'             => $tax + $shippingFee,
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
                'phone'                 => $phone,
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



    /**
     * Formats the amount to USA standard style
     * 
     * @param  float    $amount
     * @return float         
     */
    public static function formatNumber($amount)
    {
        return number_format($amount, 2);
    }




    /**
     * Updates the customer subscription_date, billing_start and billing_end if null
     * 
     * @param  Object  $obj   Subscription, CustomerStandaloneDevice, CustomerStandaloneSim
     * @return Object  $order
     */
    protected function updateCustomerDates($obj)
    {
        $customer = Customer::find($obj->customer_id);
        $order    = Order::find($obj->order_id);


        if ($customer->subscription_start_date == null && $customer->billing_start == null  && $customer->billing_end == null) {

            $customer->update([
                'subscription_start_date' => $this->carbon->toDateString(),
                'billing_start'           => $this->carbon->toDateString(),
                'billing_end'             => $this->carbon->addMonth()->subDay()->toDateString()
            ]);
        }
        $this->input = [
            'invoice_id'  => $order->invoice_id, 
            'type'        => self::DEFAULT_INT, 
            'start_date'  => $order->invoice->start_date, 
            'description' => self::DESCRIPTION,
            'taxable'     => self::DEFAULT_INT,

        ];
        return $order;
    }
    


    /**
     * Creates inovice_item for subscription
     * 
     * @param  Order      $order
     * @param  int        $subscriptionIds
     * @return Response
     */
    protected function subscriptionInvoiceItem($subscriptionIds)
    {
        $invoiceItem = null;
        
        foreach ($subscriptionIds as $index => $subscriptionId) {
            $subscription = Subscription::find($subscriptionId);

            $subarray = [
                'subscription_id' => $subscription->id,

            ];
                
            if ($subscription->device_id !== null) {

                $array = [
                    'product_type'    => self::DEVICE_TYPE,
                    'product_id'      => $subscription->device_id,
                ];

                if ($subscription->device_id === 0) {
                    $array = array_merge($array, [                        
                        'amount' => '0',
                    ]);
                } else {
                    $device = Device::find($subscription->device_id);
                    $array = array_merge($array, [
                        'type'   => 3,
                        'amount' => $device->amount_w_plan,
                    ]);
                    
                }

                $array = array_merge($subarray, $array);
                $invoiceItem = InvoiceItem::create(array_merge($this->input, $array));
                
            }


            if ($subscription->plan_id != null) {
                $plan = Plan::find($subscription->plan_id);
                $orderId = Order::where('invoice_id', $this->input['invoice_id'])->pluck('id');
                $proratedAmount = OrderGroup::where('order_id', $orderId)->where('plan_id', $subscription->plan_id)->first()->plan_prorated_amt;

                $amount = $proratedAmount == null ? $plan->amount_recurring : $proratedAmount;

                $array = [
                    'product_type' => self::PLAN_TYPE,
                    'product_id'   => $subscription->plan_id,
                    'amount'       => $amount, 
                ];

                $array = array_merge($subarray, $array);

                $invoiceItem = InvoiceItem::create(array_merge($this->input, $array));

                $regulatoryFee = $this->addRegulatorFeesToSubscription(
                    $subscription,
                    $invoiceItem->invoice,
                    self::TAX_FALSE
                );
                
            }

            if ($subscription->sim_id != null) {
                $sim = Sim::find($subscription->sim_id);
                $array = [
                    'product_type' => self::SIM_TYPE,
                    'product_id'   => $subscription->sim_id,
                    'type'         => 3,
                    'amount'       => $sim->amount_w_plan, 
                ];

                $array = array_merge($subarray, $array);

                $invoiceItem = InvoiceItem::create(array_merge($this->input, $array));
            }


            if ($subscription->subscriptionAddon) {
                foreach ($subscription->subscriptionAddon as $subAddon) {
                    $addon = Addon::find($subAddon->addon_id);

                    $order = Order::where('invoice_id', $this->input['invoice_id'])->first();
                    $orderGroupAddon = $order->orderGroup->orderGroupAddon->where('addon_id', $subAddon->addon_id)->first();
                    $proratedAmount = $orderGroupAddon != null ? $orderGroupAddon->prorated_amt : null;
                    $amount = $proratedAmount == null ? $addon->amount_recurring : $proratedAmount;

                    $array = [
                        'product_type' => self::ADDON_TYPE,
                        'product_id'   => $addon->id,
                        'type'         => 2,
                        'amount'       => $amount, 
                    ];
                    $array = array_merge($subarray, $array);
                    $invoiceItem = InvoiceItem::create(array_merge($this->input, $array));
                }
            }
            //add activation charges in invoice-item table
            $this->addActivationCharges(
                $subscription, 
                $invoiceItem->invoice,
                self::DESCRIPTION,
                self::TAX_FALSE
            );



            //$customer = app('App\Http\Controllers\Api\V1\InvoiceController')->customer();
           // \Log::info($customer);

            //add taxes to all taxable items
            
            $taxes = $this->addTaxes(
                $subscription, 
                $invoiceItem->invoice, 
                self::TAX_FALSE
            );
            


        }
       
        return $invoiceItem;
        
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

    public function applyCreditsToInvoice($creditId, $amount, $openInvoices)
    {
        
        foreach ($openInvoices as $invoice) {
            
            $totalDue       = $invoice->total_due;
            $updatedAmount  = $totalDue >= $amount ? $totalDue - $amount : 0;
            $amount         = $totalDue >= $amount ? $amount : $totalDue;
            if ($totalDue > $amount) {

                Credit::where('id', $creditId)
                ->update(
                    [
                        'applied_to_invoice'  => 1
                    ]
                );
            }
            if ($totalDue != 0) {
                $invoice->update(
                    [
                        'total_due' => $updatedAmount
                    ]
                );

                $invoice->creditToInvoice()->create(
                    [
                        'credit_id'     => $creditId,
                        'invoice_id'    => $invoice->id,
                        'amount'        => $amount,
                        'description'   => "{$amount} applied to invoice id {$invoice->id}"
                    ]
                );

            }
        }
    }

  

    public function addShippingCharges($order, $type)
    {
        $deviceFee      = 0;
        $simFee         = 0;
        $subscriptions  = $order->subscriptions;
        
        if ($subscriptions) {
            foreach ($subscriptions as $item) {

                $device = $item->invoiceItemDetail->where('product_type', self::DEVICE_TYPE)->first();
                $sim    = $item->invoiceItemDetail->where('product_type', self::SIM_TYPE)->first();

                if (isset($device)) {
                    $deviceFee = Device::find($device->product_id)->shipping_fee ? Device::find($device->product_id)->shipping_fee : 0;
                } else {
                    $deviceFee = 0;
                }
                
                if (isset($sim)) {
                    $simFee = Sim::find($sim->product_id)->shipping_fee ?  Sim::find($sim->product_id)->shipping_fee : 0;    
                } else {
                    $simFee = 0;
                }

                $order->invoice->invoiceItem()->create(
                    [
                        'invoice_id'        => $order->invoice->id,
                        'subscription_id'   => $item->id,
                        'product_type'      => '',
                        'product_id'        => null,
                        'type'              => InvoiceItem::TYPES['one_time_charges'],
                        'start_date'        => $order->invoice->start_date,
                        'description'       => self::SHIPPING,
                        'amount'            => $deviceFee + $simFee,
                        'taxable'           => 0, 
                    ]
                );
            }
        }          
    }


    protected function addShippingChargesStandalone($charges, $orderId)
    {
        $invoice = Order::find($orderId)->invoice;
       
        if ($charges > 0) {
            $invoice->invoiceItem()->create([
                'invoice_id'        => $invoice->id,
                'subscription_id'   => 0,
                'product_type'      => '',
                'product_id'        => null,
                'type'              => InvoiceItem::TYPES['one_time_charges'],
                'start_date'        => $invoice->start_date,
                'description'       => self::SHIPPING,
                'amount'            => $charges,
                'taxable'           => 0,                
            ]);
        }
        
    }


    /**
     * Creates inovice_item for customer_standalone_device
     * 
     * @param  Order      $order
     * @param  int        $deviceIds
     * @return Response
     */
    protected function standaloneDeviceInvoiceItem($standaloneDeviceIds)
    {
        $invoiceItem = null;
        $subArray = [
            'subscription_id' => 0,
            'product_type'    => self::DEVICE_TYPE,
        ];

        foreach ($standaloneDeviceIds as $index => $standaloneDeviceId) {
            $standaloneDevice = CustomerStandaloneDevice::find($standaloneDeviceId);
            $device           = Device::find($standaloneDevice->device_id);

            $array = array_merge($subArray, [
                'product_id' => $device->id,
                'type'       => 3,
                'amount'     => $device->amount,
            ]);
            $invoiceItem = InvoiceItem::create(array_merge($this->input, $array));
                
        }

        return $invoiceItem;   
    }





    /**
     * Creates inovice_item for customer_standalone_sim
     * 
     * @param  Order      $order
     * @param  int        $simIds
     * @return Response
     */
    protected function standaloneSimInvoiceItem($standaloneSimIds)
    {
        $invoiceItem = null;
        $subArray = [
            'subscription_id' => 0,
            'product_type'    => self::SIM_TYPE,
        ];

        foreach ($standaloneSimIds as $index => $standaloneSimId) {
            $standaloneSim = CustomerStandaloneSim::find($standaloneSimId);
            $sim           = Sim::find($standaloneSim->sim_id);

            $array = array_merge($subArray, [
                'product_id' => $sim->id,
                'type'       => 3,
                'amount'     => $sim->amount_alone,
            ]);

            $invoiceItem = InvoiceItem::create(array_merge($this->input, $array));
                
        }

        return $invoiceItem;   
    }




    protected function add_regulatory_fee($params, $plan)
    {

        $params['product_type'] = '';
        $params['product_id'] = null;
        $params['type'] = 5;
        $params['taxable'] = 0;

        $fee_type = $plan->regulatory_fee_type;
        $amount = $plan->regulatory_fee_amount;

        if($fee_type ==  2){
          $amount = $amount * $params['amount'];
        }
        $params['amount'] = $amount;
        $invoice_item = InvoiceItem::create($params);

        return $amount;

    }

    public function addTaxesToSubtotal($invoice)
    {
        $taxAmount = $invoice->invoiceItem()->where('type', InvoiceItem::TYPES['taxes'])->sum('amount');
        $subTotal  = $invoice->subtotal;
        $invoice->update(
            [
                'subtotal' => $taxAmount + $subTotal
            ]
        );
    }



    protected function addTax($tax_rate, $params)
    {
        $taxes = 0;
        //echo 'Rate :: ' . $tax_rate;
        //print_r($params);
        if($tax_rate && $params['taxable'] && in_array($params['type'], [2,3,4])){
            //echo ' Amount :: ' . $params['amount'];
            $taxes =  $tax_rate * $params['amount'];
        }
        //echo ' Taxes :: '. $taxes;
        return $taxes;
            
    }

    protected function getCustomerDue($customer_id){
        $dues = 0;
        $invoices = Invoice::where('customer_id', $customer_id)->where('status', 1);
        foreach($invoices as $invoice){
          $dues += $invoice->total_due;
        }
        return $dues;
    }

    protected function generate_customer_invoice($invoice, $customer)
    {
       
        $debt_amount = 0;
        $taxes = 0;
        $discounts = 0;
        $invoice_items = [];

        $tax_rate = 0;
        $tax = Tax::where('state', $customer->billing_state_id)->where('company_id', $customer->company_id)->first();
        if($tax){
          $tax_rate = ($tax->rate)/100;
        }


        $subscriptions = $customer->subscription;
        foreach($subscriptions as $subscription){

            $plan = [];

            if ( ($subscription->status == 'shipping' || $subscription->status == 'for-activation') ||  ($subscription->status == 'active' || $subscription->upgrade_downgrade_status == 'downgrade-scheduled')  ) {

                $plan = $subscription->plan;

            } else if ($subscription->status == 'active' || $subscription->upgrade_downgrade_status = 'downgrade-scheduled') {
                $plan = $subscription->new_plan;

            } else {
                continue;
            }

            $params['product_id'] = $plan['id'];
            $params['description'] = $plan['description'];
            $params['amount'] = $plan['amount_recurring'];

            $params['taxable'] = $plan['taxable'];

            $taxes += $this->addTax($tax_rate, $params);
            $debt_amount += $params['amount'];

            $invoice_item = InvoiceItem::create($params);
            array_push($invoice_items, ['item' => $invoice_item, 'taxes'=>$tax_rate ] );

            $debt_amount += $this->add_regulatory_fee($params, $plan);

            $subscription_addons = $subscription->subscription_addon;
            $addon = [];
            foreach($subscription_addons as $s_addon){

                if($s_addon['status'] == 'removal-scheduled' || $s_addon['status'] == 'for-removal'){
                    continue;
                }
                $addon = $s_addon->addon;
                $params = [
                    'invoice_id' => $invoice->id,
                    'subscription_id' => $s_addon['subscription_id'],
                    'product_type' => 'addon',
                    'product_id' => $addon['id'],
                    'type' => 2,
                    'description' => $addon['description'],
                    'amount' => $addon['amount_recurring'],
                    'start_date' => $invoice->start_date,
                    'taxable' => $s_addon->subscription->plan['taxable'] // Replace this with this subscription->plan->taxable
                ];

                $taxes += $this->addTax($tax_rate, $params);
                $debt_amount += $params['amount'];

                $invoice_item = InvoiceItem::create($params);
                array_push($invoice_items, ['item' => $invoice_item, 'taxes'=>$tax_rate ] );

            }

        }


        foreach($customer->pending_charge as $pending_charg){
            if($pending_charg->invoice_id == 0){
                $params = [
                    'type'=>$pending_charg['type'],
                    'amount'=>$pending_charg['amount'],
                    'description'=>$pending_charg['description']
                ];

                $taxes += $this->addTax($tax_rate, $params);
                $debt_amount += $params['amount'];
                $invoice_item = InvoiceItem::create($params);
                array_push($invoice_items, ['item' => $invoice_item, 'taxes'=>$tax_rate ] );
           
            }
        
            //update pending charge
            $pendingcharge = $pending_charg->update(['invoice_id'=>$invoice->id]);
        }


        //lookup coupons

        $coupon_discounts = 0;
        // $customer_coupon = CustomerCoupon::where('customer_id', $customer->id)->where('cycles_remaining','>' ,0)->get();

        // for($customer_coupon as $cc){

        // }
     

        //add tax
        $params = [
            'invoice_id' => $invoice->id,
            'product_type' => 'taxes',
            'type' => 7,
            'amount' => $taxes,
            'description' => 'all taxes'
        ];

        $invoice_item = InvoiceItem::create($params);
   
        $subtotal = $debt_amount - $coupon_discounts + $taxes;

        $dues = $this->getCustomerDue($customer->id);

        $invoice->update([
            'total_due'=>$dues,
            'subtotal'=>$subtotal
        ]);


        $billing_date = strtotime($invoice->start_date);
   
        $invoice = [
            'billing_date' => [ 
                'year' => date("y", $billing_date),
                'month' => date("m", $billing_date),
                'day' => date("d", $billing_date),
            ],
            'company' => $customer->company,
            'number' => $invoice->id,
            'period_beginning' => $invoice->start_date,
            'period_ending' => $invoice->end_date,
            'due_date' => $invoice->due_date,
            'subtotal' => $subtotal,
            'total_due' => $dues,
            'items' => $invoice_items
        ];

        //add activation charges

        $pdf = PDF::loadView('templates/invoice', compact('invoice'))->setPaper('a4', 'landscape');
        $pdf->save('invoice/invoice.pdf');

    }

    protected function generate_new_invoice($customers){
    
        foreach ($customers as $customer) {

            // check invoice type and start_date
            $invoice_type_1 = false;

            if(count($customer->invoice)){
                foreach($customer->invoice as $invoice){
                    if($invoice->type == 1 && $invoice->start_date > $customer->billing_end){
                        $invoice_type_1 = true;
                        break;
                    }
                }   
            }

            if($invoice_type_1){ continue; }

            // Add row to invoice
            $_enddate = $customer->end_date;
            $start_date = date ("Y-m-d", strtotime ($_enddate ."+1 days"));
            $end_date = date ("Y-m-d", strtotime ( $start_date ."+1 months"));
            $due_date = $customer->billing_end;
            $invoice = Invoice::create([
                'customer_id'  => $customer->id,
                'end_date'     => $start_date,
                'start_date'   => $end_date,
                'due_date'     => $due_date,
                'type'         => 1,
                'status'       => 1,
                'subtotal'     => 0,
                'total_due'    => 0,
                'prev_balance' => 0
            ]);
            $this->generate_customer_invoice($invoice, $customer);
            
        }
    }


}
