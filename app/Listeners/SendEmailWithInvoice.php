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

class SendEmailWithInvoice
{
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
        $order = $event->order;

        $invoice = $this->setData($order);

        $pdf = PDF::loadView('templates/onetime-invoice', compact('invoice'))->setPaper('letter', 'portrait');
        
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
     * Sets invoice data for pdf generation
     * 
     * @param Order     $order
     */
    protected function setData($order)
    {
        $data = [];
        if ($order) {
            if ($order->invoice->type == Invoice::TYPES['one-time']) {
                $serviceCharges = $order->invoice->cal_service_charges;
                $taxes          = $order->invoice->cal_taxes;
                $credits        = $order->invoice->cal_credits;
                $totalCharges   = $order->invoice->cal_total_charges;
                $oneTimeCharges = $order->invoice->cal_onetime;
                $usageCharges   = $order->invoice->cal_usage_charges;
                $planCharges    = $order->invoice->cal_plan_charges;
                $subtotal       = $order->invoice->subtotal;


                $data = [
                    'invoice_num'            => $order->invoice->id,
                    'subscriptions'          => [],
                    'start_date'             => $order->invoice->start_date,
                    'end_date'               => $order->invoice->end_date,
                    'due_date'               => $order->invoice->due_date,
                    'total_due'              => $order->invoice->total_due,
                    'subtotal'               => $order->invoice->subtotal,
                    'today_date'             => $this->carbon->toFormattedDateString(),
                    'customer_name'          => $order->customer->full_name,
                    'customer_address'       => $order->customer->shipping_address1,
                    'customer_zip_address'   => $order->customer->zip_address,
                    'service_charges'        => number_format($serviceCharges, 2),
                    'taxes'                  => number_format($taxes, 2),
                    'credits'                => number_format($credits, 2),
                    'total_charges'          => number_format($totalCharges, 2),
                    'total_one_time_charges' => self::formatNumber($oneTimeCharges),
                    'total_usage_charges'    => self::formatNumber($usageCharges),
                    'plan_charges'           => self::formatNumber($planCharges),
                ];
            }
        }
        if ($order->subscriptions) {

            foreach ($order->subscriptions as $subscription) {
                $planCharges    = $subscription->cal_plan_charges;
                $onetimeCharges = $subscription->cal_onetime_charges;
                $usageCharges   = $subscription->cal_usage_charges;
                $tax            = $subscription->cal_taxes;

                $subscriptionData = [
                    'subscription_id' => $subscription->id,
                    'plan_charges'    => number_format($planCharges, 2),
                    'onetime_charges' => number_format($onetimeCharges, 2),
                    'phone'           => $subscription->phone_number,
                    'usage_charges'   => $usageCharges,
                    'tax'             => $tax
                ];

                array_push($data['subscriptions'], $subscriptionData);
            }
        }
        return $data;
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

}
