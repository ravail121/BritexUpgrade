<?php

namespace App\Listeners;

use PDF;
use Mail;
use Config;
use Carbon\Carbon;
use App\Model\Order;
use App\Model\Invoice;
use App\Model\Company;
use App\Events\InvoiceGenerated;
use App\Model\BusinessVerification;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Notifications\Notifiable;
use Illuminate\Notifications\Notification;
use App\Notifications\EmailWithAttachment;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Model\Subscription;
use App\Model\InvoiceItem;
use App\Model\Plan;
use App\Model\Addon;
use App\Model\Device;
use App\Model\Sim;

class SendEmailWithInvoice
{
    const SIM_TYPE    = 'sim';
    const PLAN_TYPE   = 'plan';
    const ADDON_TYPE  = 'addon';
    const DEVICE_TYPE = 'device';
    const DESCRIPTION = 'Activation Fee';
    const SHIPPING    = 'Shipping Fee';
    const ONETIME     = 3;
    const TAXES       = 7;
    const COUPONS     = 6;

    use Notifiable;

    /**
     * Date-Time variable
     * 
     * @var $carbon
     */
    public $carbon;



    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct(Carbon $carbon)
    {
        $this->carbon = $carbon;
    }

    /**
     * Handle the event.
     *
     * @param  BusinessVerificationCreated  $event
     * @return void
     */
    public function handle(InvoiceGenerated $event)
    {
        
        $order         = $event->order;
        
        $customerOrder = Order::find($order->id);

        $orderType     = $customerOrder->invoice->type;
       
        $invoice = $this->invoice($customerOrder);
        
        if ($orderType  == Invoice::TYPES['one-time']) {
            
            $pdf = PDF::loadView('templates/onetime-invoice', compact('invoice'))->setPaper('letter', 'portrait');

        } elseif ($orderType  == Invoice::TYPES['monthly']) {
           
            $pdf = PDF::loadView('templates/monthly-invoice', compact('invoice'))->setPaper('letter', 'portrait');

        }
        
        $configurationSet = $this->setMailConfiguration($order);
        
        if ($configurationSet) {
            
            return false;
        }

        $bizVerification = BusinessVerification::find($order->customer->business_verification_id);
       
        $bizVerification->notify(new EmailWithAttachment($order, $pdf));        
    }


    /**
     * This method sets the Configuration of the Mail according to the Company
     * 
     * @param Order $order
     * @return boolean
     */
    protected function setMailConfiguration($order)
    {
        $company = Company::find($order->company_id);
        $config = [
            'driver'   => $company->smtp_driver,
            'host'     => $company->smtp_host,
            'port'     => $company->smtp_port,
            'username' => $company->smtp_username,
            'password' => $company->smtp_password,
        ];
        
        Config::set('mail',$config);
        return false;
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
     * Sets invoice data for pdf generation
     * 
     * @param Order     $order
     */
    protected function invoice($order)
    {
        $invoice = [];
        
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
            $subscriptionTotal      = $subscriptionItems->where('type', '!=',InvoiceItem::TYPES['coupon'])->sum('amount');
                                              
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
                'date_payment'                  =>   $order->credits->first() ? Carbon::parse($order->credits->first()->date)->format('m/d/Y') : '',
                'date_credit'                   =>   $order->invoice->creditsToInvoice->first() ? Carbon::parse($order->invoice->creditsToInvoice->first()->created_at)->format('m/d/Y') : '',
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
            
            
        }
       
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

            if ($item['product_type'] == InvoiceItem::PRODUCT_TYPE['device']  && $item['amount']) {

                $name      = Device::find($item['product_id'])->name;
                $devices[] = ['name' => $name, 'amount' => $item['amount']];

            }

            if ($item['product_type'] == InvoiceItem::PRODUCT_TYPE['sim']  && $item['amount']) {

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
            ->where('amount', '!=', null);;
        
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

        }

        $arr = [
            'invoice_num'           => $order->invoice->id,
            'subscriptions'         => [],
            'end_date'              => Carbon::parse($order->invoice->end_date)->format('m/d/Y'),
            'due_date'              => Carbon::parse($order->invoice->due_date)->format('m/d/Y'),
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

    
}
