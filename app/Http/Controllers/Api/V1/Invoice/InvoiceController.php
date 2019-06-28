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

                $order       = $this->updateCustomerDates($subscription);

                $invoiceItem  = $this->subscriptionInvoiceItem($invoice['subscription_id']);

                $msg = (!$invoiceItem) ? 'Subscription Invoice item was not generated' : 'Invoice item generated successfully.'; 

                $order = Order::where('hash', $request->hash)->first();

                $currentInvoice =  $order->invoice;
                
            }
            if(isset($invoice['same_subscription_id'])){
                $invoiceItem  = $this->samePlanUpgradeInvoiceItem($invoice['same_subscription_id'], $request->order_id);
            }
            if (isset($invoice['customer_standalone_device_id'])) {
               
                $orderId = CustomerStandaloneDevice::find($invoice['customer_standalone_device_id'])->first()->order_id;
               
                $standaloneDevice = CustomerStandaloneDevice::find($invoice['customer_standalone_device_id'][0]);
               
                $order = $this->updateCustomerDates($standaloneDevice);
               
                $invoiceItem = $this->standaloneDeviceInvoiceItem($invoice['customer_standalone_device_id']);
               
                $taxes = $this->addTaxesToStandalone(Order::find($orderId)->invoice, self::TAX_FALSE, self::DEVICE_TYPE);
               
                $msg = (!$invoiceItem) ? 'Standalone Device Invoice item was not generated' : 'Invoice item generated successfully.' ;
            }
            if (isset($invoice['customer_standalone_sim_id'])) {

                $orderId = CustomerStandaloneSim::find($invoice['customer_standalone_sim_id'])->first()->order_id;

                $standaloneSim = CustomerStandaloneSim::find($invoice['customer_standalone_sim_id'][0]);

                $order = $this->updateCustomerDates($standaloneSim);
                        
                $invoiceItem = $this->standaloneSimInvoiceItem($invoice['customer_standalone_sim_id']);

                $taxes = $this->addTaxesToStandalone(Order::find($orderId)->invoice, self::TAX_FALSE, self::SIM_TYPE);

                $msg = (!$invoiceItem) ? 'Standalone Sim Invoice item was not generated' : 'Invoice item generated successfully.';

            }

            $order = Order::where('hash', $request->hash)->first();
            
            $currentInvoice =  $order->invoice;

            $this->storeCoupon($request->couponAmount, $request->couponCode, $currentInvoice);

        }else if($request->status == 'Without Payment'){
            $this->createInvoice($request);
            return $this->respond($msg);
        }


        $order = Order::where('hash', $request->hash)->first();

        $this->addShippingCharges($order);
    
        if ($request->customer_id) {
            $this->availableCreditsAmount($request->customer_id);
        }

        $this->ifTotalDue($order);

        if(isset($order)) {
            event(new InvoiceGenerated($order));
        }

        return $this->respond($msg);
    }

    public function getTax(Request $request)
    {
        $order      = Order::where('hash', $request->hash)->first();
        $taxes      = $order->invoice->invoiceItem->where('type', self::TAXES)->sum('amount');
        $shipping   = $order->invoice->invoiceItem->where('description', self::SHIPPING)->sum('amount');
        return ['taxes' => $taxes, 'shipping' => $shipping];
    }

    public function getCoupons(Request $request)
    {
        $coupons = Order::where('hash', $request->hash)->first()->invoice->invoiceItem->where('type', self::COUPONS)->sum('amount');
        return ['coupons' => $coupons];
    }
   
    public function storeCoupon($couponAmount, $couponCode, $invoice)
    {
        
        //store coupon in invoice_items.
        if ($couponAmount) {
            $invoice->invoiceItem()->create(
                [
                    'subscription_id' => null,
                    'product_type'    => '',
                    'product_id'      => null,
                    'type'            => InvoiceItem::TYPES['coupon'],
                    'description'     => "(Coupon) ".$couponCode,
                    'amount'          => $couponAmount,
                    'start_date'      => $invoice->start_date,
                    'taxable'         => self::TAX_FALSE,
                ]
            );

            $couponNumUses   = Coupon::where('code', $couponCode)->pluck('num_uses')->first();

            Coupon::where('code', $couponCode)->update([
                'num_uses' => $couponNumUses + 1 
            ]);

            //store coupon in customer_coupon table if eligible
            $coupon         = Coupon::where('code', $couponCode)->first();
            $couponCycles   = $coupon->num_cycles;
            $couponId       = $coupon->id;

            $customerCoupon = [
                'customer_id'       => $invoice->customer_id,
                'coupon_id'         => $couponId,
                
            ];

            $customerCouponInfinite = [
                'cycles_remaining'  => -1
            ];

            $customerCouponFinite = [
                'cycles_remaining'  => $couponCycles - 1
            ];

            if ($couponCycles > 1) {

                $data = array_merge($customerCoupon, $customerCouponFinite);
                CustomerCoupon::create($data);

            } elseif ($couponCycles == 0) {
                
                $data = array_merge($customerCoupon, $customerCouponInfinite);
                CustomerCoupon::create($data);

            }
        }


    }

    protected function ifTotalDue($order)
    {
        $totalAmount    = $order->invoice->subtotal;
        $paidAmount     = $order->invoice->creditsToInvoice->sum('amount');
        $totalDue       = $totalAmount > $paidAmount ? $totalAmount - $paidAmount : 0;
       
        $order->invoice->update(
            [
                'total_due'     => $totalDue
            ]
        );
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
            $taxAndShipping         = $taxes;
            $totalCredits           = $order->credits->sum('amount');
            $oldUsedCredits         = $order->credits->first() ? $order->invoice->creditsToInvoice->where('credit_id', '!=', $order->credits->first()->id)->sum('amount') : $order->invoice->creditsToInvoice->sum('amount');
            $totalCreditsToInvoice  = $order->invoice->creditsToInvoice->sum('amount');
            $totalCoupons           = $order->invoice->invoiceItem->where('type', self::COUPONS)->sum('amount');
            $accountChargesDiscount = $totalAccountCharges - $totalCoupons - $shippingFee;
            $totalLineCharges       = $planCharges + $oneTimeCharges + $taxes + $usageCharges - $totalCoupons;
            $standalone             = $order->invoice->invoiceItem->where('subscription_id', null);
            $standaloneItems        = $standalone->where('type', InvoiceItem::TYPES['one_time_charges']);
            $standaloneTaxes        = $standalone->where('type', InvoiceItem::TYPES['taxes'])->sum('amount');
            $standaloneRegulatory   = $standalone->where('type', InvoiceItem::TYPES['regulatory_fee'])->sum('amount');
            $standaloneCoupons      = $standalone->where('type', InvoiceItem::TYPES['coupon'])->sum('amount');
            $standaloneTotal        = $standalone->where('description', '!=', 'Shipping Fee')->where('type', '!=', InvoiceItem::TYPES['coupon'])->sum('amount');
            $subscriptionItems      = $order->invoice->invoiceItem->where('subscription_id', '!=', null)->where('description', '!=', 'Shipping Fee');
            $subscriptionTaxesFees  = $subscriptionItems->whereIn('type',[InvoiceItem::TYPES['taxes'], InvoiceItem::TYPES['regulatory_fee']])->sum('amount');
            $subscriptionCoupons    = $subscriptionItems->where('type', InvoiceItem::TYPES['coupon'])->sum('amount');
            $subscriptionTotal      = $subscriptionItems->sum('amount');
                                              
            $invoice = [
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
                'subtotal'                      =>   self::formatNumber($order->invoice->subtotal),
                'shipping_fee'                  =>   self::formatNumber($shippingFee),
                'plans'                         =>   $this->plans($order),
                'addons'                        =>   $this->addons($order),
                'tax_and_shipping'              =>   self::formatNumber($taxAndShipping),
                'standalone_data'               =>   $this->setStandaloneItemData($order),
                'total_old_credits'             =>   self::formatNumber($oldUsedCredits),
                'total_credits_to_invoice'      =>   self::formatNumber($totalCreditsToInvoice),
                'total_payment'                 =>   self::formatNumber($order->credits->sum('amount')),
                'total_used_credits'            =>   self::formatNumber($totalCredits + $oldUsedCredits),
                'date_payment'                  =>   $order->credits->first() ? $order->credits->first()->date : '',
                'date_credit'                   =>   $order->invoice->creditsToInvoice->first() ? Carbon::parse($order->invoice->creditsToInvoice->first()->created_at)->toDateString() : '',
                'credits_and_coupons'           =>   self::formatNumber($totalCreditsToInvoice + $totalCoupons),
                'total_coupons'                 =>   self::formatNumber($totalCoupons),
                'account_charges_discount'      =>   self::formatNumber($accountChargesDiscount),
                'total_line_charges'            =>   self::formatNumber($totalLineCharges),
                'total_account_summary_charge'  =>   self::formatNumber($planCharges + $oneTimeCharges + $usageCharges + $taxAndShipping - $credits),
                'customer_auto_pay'             =>   $order->customer->auto_pay,
                'company_logo'                  =>   $order->customer->company->logo,
                'reseller_phone_number'         =>   $this->phoneNumberFormatted($order->customer->company->support_phone_number),
                'reseller_domain'               =>   $order->customer->company->url,
                'standalone_items'              =>   $this->getItemDetails($standaloneItems),
                'one_time_standalone'           =>   self::formatNumber($standaloneItems->where('description', '!=', 'Shipping Fee')->sum('amount')),
                'standalone_tax'                =>   self::formatNumber($standaloneTaxes),
                'standalone_regulatory'         =>   self::formatNumber($standaloneRegulatory),
                'standalone_total_taxes_fees'   =>   self::formatNumber($standaloneTaxes + $standaloneRegulatory),
                'standalone_coupons'            =>   self::formatNumber($standaloneCoupons),
                'standalone_total'              =>   self::formatNumber($standaloneTotal - $standaloneCoupons),
                'subscription_items'            =>   $this->getItemDetails($subscriptionItems),
                'subscription_act_fee'          =>   self::formatNumber($subscriptionItems->where('product_id', null)->where('type', InvoiceItem::TYPES['one_time_charges'])->sum('amount')),
                'subscription_total_one_time'   =>   self::formatNumber($subscriptionItems->where('type', InvoiceItem::TYPES['one_time_charges'])->sum('amount')),
                'subscription_total_tax'        =>   self::formatNumber($subscriptionItems->where('type', InvoiceItem::TYPES['taxes'])->sum('amount')),
                'subscription_total_reg_fee'    =>   self::formatNumber($subscriptionItems->where('type', InvoiceItem::TYPES['regulatory_fee'])->sum('amount')),
                'subscription_total_tax_fee'    =>   self::formatNumber($subscriptionTaxesFees),
                'subscription_usage_charges'    =>   self::formatNumber($subscriptionItems->where('type', InvoiceItem::TYPES['usage_charges'])->sum('amount')),
                'subscription_coupons'          =>   self::formatNumber($subscriptionCoupons),
                'subscription_total'            =>   self::formatNumber($subscriptionTotal - $subscriptionCoupons)
            ];
            
            $invoice = array_merge($data, $invoice);
 
            if ($order->invoice->type == Invoice::TYPES['one-time']) {
                $pdf = PDF::loadView('templates/onetime-invoice', compact('invoice'));
                return $pdf->download('invoice.pdf');
            
            } else {
                $pdf = PDF::loadView('templates/monthly-invoice', compact('invoice'))->setPaper('letter', 'portrait');                    
                return $pdf->download('invoice.pdf');
                
            }

        }
        return 'Sorry, something went wrong please try again later......';
        
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

            if ($item['product_type'] == InvoiceItem::PRODUCT_TYPE['device']) {

                $name      = Device::find($item['product_id'])->name;
                $devices[] = ['name' => $name, 'amount' => $item['amount']];

            }

            if ($item['product_type'] == InvoiceItem::PRODUCT_TYPE['sim']) {

                $name      = Sim::find($item['product_id'])->name;
                $sims[]    = ['name' => $name, 'amount' => $item['amount']];

            }

        }
        
        return ['devices' => $devices, 'sims' => $sims];

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
            'total_due'             => self::formatNumber($order->invoice->total_due),
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
        $coupons      = $standaloneItems->where('type', InvoiceItem::TYPES['coupon'])->sum('amount');
        
        $standalone = [
            'standalone_onetime_charges'  => self::formatNumber($deviceAndSim),
            'standalone_shipping_fee'     => self::formatNumber($shipping),
            'taxes'                       => self::formatNumber($tax),
            'coupons'                     => self::formatNumber($coupons),  
            'total'                       => self::formatNumber($tax + $deviceAndSim - $coupons),
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
                'phone'           => $this->phoneNumberFormatted($subscription->phone_number),
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
     * Updates the customer subscription_date, baddRegulatorFeesToSubscriptiontart and billing_end if null
     * 
     * @param  Object  $obj   Subscription, CustaddRegulatorFeesToSubscriptiondaloneDevice, CustomerStandaloneSim
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
        $paidInvoice = 0;
        $invoiceItem = null;
        $order = Order::where('invoice_id', $this->input['invoice_id'])->first();
        foreach ($subscriptionIds as $index => $subscriptionId) {
            $subscription = Subscription::find($subscriptionId);

            $subarray = [
                'subscription_id' => $subscription->id,

            ];
                
            if ($subscription->device_id !== null && $subscription->upgrade_downgrade_status == null) {

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
                        'taxable' => $device->taxable
                    ]);
                    
                }

                $array = array_merge($subarray, $array);
                $invoiceItem = InvoiceItem::create(array_merge($this->input, $array));
                
            }else{
                $date = Carbon::today()->addDays(6)->endOfDay();
                $invoice = Invoice::where([['customer_id', $subscription->customerRelation->id],['type', '1'],['status','2']])->whereBetween('start_date', [Carbon::today()->startOfDay(), $date])->first();
                if(isset($invoice)){
                    $paidInvoice = 1;
                }
            }

            if ($subscription->plan_id != null) {
                $plan = Plan::find($subscription->plan_id);

                if($subscription->upgrade_downgrade_status == null){
                    $proratedAmount = $order->planProRate($plan->id);
                    $amount = $proratedAmount == null ? $plan->amount_recurring : $proratedAmount;
                }else{
                    $amount = $plan->amount_recurring - $subscription->oldPlan->amount_recurring;
                }

                $array = [
                    'product_type' => self::PLAN_TYPE,
                    'product_id'   => $subscription->plan_id,
                    'amount'       => $amount,
                    'taxable'      => $plan->taxable
                ];
                if($subscription->upgrade_downgrade_status =='for-upgrade'){
                    \Log::info($subscription);
                    $array['description'] = 'Upgrade from plan '.$subscription->old_plan_id.' to plan '.$subscription->plan_id;  
                }
                
                $array = array_merge($subarray, $array);

                $invoiceItem = InvoiceItem::create(array_merge($this->input, $array));

                if($subscription->upgrade_downgrade_status == null){
                    //add REGULATORY FEE charges in invoice-item table
                    $regulatoryFee = $this->addRegulatorFeesToSubscription(
                        $subscription,
                        $invoiceItem->invoice,
                        self::TAX_FALSE,
                        $order
                    );

                    //add activation charges in invoice-item table
                    $this->addActivationCharges(
                        $subscription, 
                        $invoiceItem->invoice,
                        self::DESCRIPTION,
                        self::TAX_FALSE
                    );
                }elseif($paidInvoice == 1){
                    $invoiceItem = InvoiceItem::create(array_merge($this->input, $array));
                }   
            }

            if ($subscription->sim_id != null && $subscription->upgrade_downgrade_status == null) {
                $sim = Sim::find($subscription->sim_id);
                $array = [
                    'product_type' => self::SIM_TYPE,
                    'product_id'   => $subscription->sim_id,
                    'type'         => 3,
                    'amount'       => $sim->amount_w_plan, 
                    'taxable'      => $sim->taxable
                ];

                $array = array_merge($subarray, $array);

                $invoiceItem = InvoiceItem::create(array_merge($this->input, $array));
            }
            
            $order = Order::where('invoice_id', $this->input['invoice_id'])->first();
            if($subscription->upgrade_downgrade_status != null){
                $orderGroupId = OrderGroup::whereOrderId($order->id)->pluck('id');
                $orderGroupAddonId = OrderGroupAddon::whereIn('order_group_id', $orderGroupId)->where('subscription_id', $subscription->id)->pluck('addon_id');


                $subscriptionAddons = $subscription->subscriptionAddon->whereIn('addon_id', $orderGroupAddonId);  
            }else{
                $subscriptionAddons = $subscription->subscriptionAddon;
            }
            

            if ($subscriptionAddons) {
                
                foreach ($subscriptionAddons as $subAddon) {

                    $addon = Addon::find($subAddon->addon_id);

                    if($subscription->upgrade_downgrade_status == null){
                        $isProrated = $order->orderGroup->where('plan_prorated_amt', '!=', null);

                        if ($isProrated) {
                            $proratedAmount = $order->calProRatedAmount($addon->amount_recurring);
                        }
                        $addonAmount    = $proratedAmount >= 0 ? $proratedAmount : $addon->amount_recurring; 
                    }else{
                        if($subAddon->status == 'removal-scheduled' || $subAddon->status == 'removed' ){
                            $addonAmount = 0; 
                        }else{
                            $addonAmount = $addon->amount_recurring;
                        }
                    }
                                       

                    $array = [
                        'product_type' => self::ADDON_TYPE,
                        'product_id'   => $addon->id,
                        'type'         => 2,
                        'amount'       => $addonAmount,
                        'taxable'      => $addon->taxable
                    ];

                    $array = array_merge($subarray, $array);

                    $invoiceItem = InvoiceItem::create(array_merge($this->input, $array));

                    if($paidInvoice == 1 && $subscription->upgrade_downgrade_status == null){
                        $invoiceItem = InvoiceItem::create(array_merge($this->input, $array));
                    }
                }
            }

            if($subscription->upgrade_downgrade_status == null){
                //add taxes to subscription items only
                $taxes = $this->addTaxesToSubscription(
                    $subscription, 
                    $invoiceItem->invoice, 
                    self::TAX_FALSE
                );
            }else{
                $this->addTaxesToUpgrade($order->invoice, self::TAX_FALSE);
                if($paidInvoice == 1){
                    $this->addTaxesToUpgrade($order->invoice, self::TAX_FALSE);
                }
            }


        }
       
        return $invoiceItem;
        
    
    }

    /**
     * Creates inovice_item for samePlanSubcription
     * 
     * @param  Order      $order
     * @param  int        $subscriptionIds
     * @return Response
     */
    protected function samePlanUpgradeInvoiceItem($subscriptionIds, $orderId)
    {
        $invoiceItem = null;
        $order = Order::find($orderId);

        foreach ($subscriptionIds as $index => $subscriptionId) {
            $subscription = Subscription::find($subscriptionId);

            $paidInvoice = 0;
            $date = Carbon::today()->addDays(6)->endOfDay();
            $mounthlyInvoice = Invoice::where([['customer_id', $subscription->customerRelation->id],['type', '1'],['status','2']])->whereBetween('start_date', [Carbon::today()->startOfDay(), $date])->first();
            if(isset($invoice)){
                $paidInvoice = 1;
            }
            
            $orderGroupId = OrderGroup::whereOrderId($order->id)->pluck('id');
            $orderGroupAddonId = OrderGroupAddon::whereIn('order_group_id', $orderGroupId)->where('subscription_id', $subscription->id)->pluck('addon_id');


            $subscriptionAddons = $subscription->subscriptionAddon->whereIn('addon_id', $orderGroupAddonId);  
            

            if ($subscriptionAddons) {
                
                foreach ($subscriptionAddons as $subAddon) {

                    $addon = Addon::find($subAddon->addon_id);

                    if($subAddon->status == 'removal-scheduled' || $subAddon->status == 'removed' ){
                        $addonAmount = 0; 
                    }else{
                        $addonAmount = $addon->amount_recurring;
                    }
                                       

                    $array = [
                        'product_type'    => self::ADDON_TYPE,
                        'product_id'      => $addon->id,
                        'type'            => 2,
                        'amount'          => $addonAmount,
                        'taxable'         => $addon->taxable,
                        'subscription_id' => $subscription->id,
                        'invoice_id'      => $order->invoice_id,
                        'start_date'      => $order->invoice->start_date,
                        'description'     => self::DESCRIPTION,
                    ];

                    $invoiceItem = InvoiceItem::create($array);

                    if($paidInvoice == 1){
                        $invoiceItem = InvoiceItem::create($array);
                        $this->addTaxesToUpgrade($order->invoice, self::TAX_FALSE);
                    }
                }

                $this->addTaxesToUpgrade($order->invoice, self::TAX_FALSE);
            }
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


    public function addShippingCharges($order)
    {
        $devices = $order->invoice->invoiceItem->where('product_type', self::DEVICE_TYPE);
        $sims    = $order->invoice->invoiceItem->where('product_type', self::SIM_TYPE);

        $totalShippingFee = [];

        foreach ($devices as $device) {
            $shippingFee = Device::find($device->product_id)->shipping_fee;
            if ($shippingFee != null) {
                $totalShippingFee[] = $shippingFee;
            }
        }

        foreach ($sims as $sim) {
            $shippingFee = Sim::find($sim->product_id)->shipping_fee;
            if ($shippingFee != null) {
                $totalShippingFee[] = $shippingFee;
            }
        }

        if (array_sum($totalShippingFee) > 0) {
            
            $order->invoice->invoiceItem()->create(
                [
                    'invoice_id'        => $order->invoice->id,
                    'subscription_id'   => null,
                    'product_type'      => '',
                    'product_id'        => null,
                    'type'              => InvoiceItem::TYPES['one_time_charges'],
                    'start_date'        => $order->invoice->start_date,
                    'description'       => self::SHIPPING,
                    'amount'            => array_sum($totalShippingFee),
                    'taxable'           => 0,
                ]
            );
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
                'taxable'    => $device->taxable
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
                'taxable'    => $sim->taxable
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
        
        $taxAmount              = $invoice->invoiceItem()->where('type', InvoiceItem::TYPES['taxes'])->sum('amount');
        $subTotalByInvoiceItems = $invoice->invoiceItem->sum('amount');
        $subTotal               = $invoice->subtotal;
        
        if ($subTotal != $subTotalByInvoiceItems && $subTotalByInvoiceItems - $subTotal == $taxAmount) {
            $invoice->update(
                [
                    'subtotal' => $taxAmount + $subTotal
                ]
            );
        }

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

    public function createInvoice(Request $request)
    {
        $data=$request->validate([
            'customer_id'    => 'required',
            'order_hash'     => 'required',
            'order_groups'   => 'required',
        ]);

        $customer = Customer::find($data['customer_id']);
        $end_date = Carbon::parse($customer->billing_end)->addDays(1);
        $order = Order::whereHash($data['order_hash'])->first();

        $invoice = Invoice::create([
            'customer_id'             => $customer->id,
            'end_date'                => $end_date,
            'start_date'              => $customer->billing_start,
            'due_date'                => $customer->billing_end,
            'type'                    => 2,
            'status'                  => 1,
            'subtotal'                => 0,
            'total_due'               => 0,
            'prev_balance'            => 0,
            'payment_method'          => 1,
            'notes'                   => 'notes',
            'business_name'           => $customer->company_name, 
            'billing_fname'           => $customer->billing_fname, 
            'billing_lname'           => $customer->billing_lname, 
            'billing_address_line_1'  => $customer->billing_address1, 
            'billing_address_line_2'  => $customer->billing_address2, 
            'billing_city'            => $customer->billing_city, 
            'billing_state'           => $customer->billing_state_id, 
            'billing_zip'             => $customer->billing_zip, 
            'shipping_fname'          => $order->shipping_fname, 
            'shipping_lname'          => $order->shipping_lname, 
            'shipping_address_line_1' => $order->shipping_address1, 
            'shipping_address_line_2' => $order->shipping_address2, 
            'shipping_city'           => $order->shipping_city, 
            'shipping_state'          => $order->shipping_state_id, 
            'shipping_zip'            => $order->shipping_zip,
        ]);

        $orderCount = Order::where('customer_id', $order->customer_id)->where('status', 1)->max('order_num');
        

        $order->update([
            'invoice_id' => $invoice->id,
            'status' => '1',
            'order_num' => $orderCount + 1,
        ]);

        $this->createInvoiceItem($data['order_groups'], $invoice);
    }

    private function createInvoiceItem($orderGroups, $invoice)
    {
        foreach ($orderGroups as $orderGroup) {
            $subscription = Subscription::find($orderGroup['subscription']['id']);
            if($subscription->upgrade_downgrade_date_submitted == "for-upgrade"){
                $description = 'Upgrade from '.$subscription['old_plan_id'].' to '.$subscription['new_plan_id'];
            }else{
                $description = 'Downgrade from '.$subscription['old_plan_id'].' to '.$subscription['new_plan_id'];
            }
            $data = [
                'invoice_id'      => $invoice->id,
                'subscription_id' => $subscription['id'],
                'product_type'    => self::PLAN_TYPE,
                'product_id'      => $orderGroup['plan']['id'],
                'amount'          => 0,
                'start_date'      => $invoice->start_date,
                'type'            => 1,
                'taxable'         => $orderGroup['plan']['taxable'],
                'description'     => $description,
            ];
            InvoiceItem::create($data);
            if(isset($orderGroup['addons'])){
                $addonData = [
                    'invoice_id'      => $invoice->id,
                    'subscription_id' => $subscription['id'],
                    'product_type'    => self::ADDON_TYPE,
                    'amount'          => 0,
                    'type'            => 1,
                    'start_date'      => $invoice->start_date,
                    'description'     => $description,
                ];

                foreach ($orderGroup['addons'] as $addon) {

                    $addonData['product_id'] = $addon['id'];
                    $addonData['taxable'] = $addon['taxable'];

                    InvoiceItem::create($addonData);
                }
            }
        }
    }
}
