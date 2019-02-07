<?php

namespace App\Listeners;

use PDF;
use Mail;
use Config;
use App\Model\Order;
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
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
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

        $invoice = [
            'start_date' => $order->invoice->start_date,
            'end_date'   => $order->invoice->end_date,
            'due_date'   => $order->invoice->due_date,
            'total_due'  => $order->invoice->total_due,
            'subtotal'   => $order->invoice->subtotal,
         ];
        $pdf = PDF::loadView('templates/invoice', compact('invoice'))->setPaper('a4', 'landscape')->stream();

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

}
