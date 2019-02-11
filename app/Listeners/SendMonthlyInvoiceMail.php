<?php

namespace App\Listeners;

use PDF;
use Mail;
use Config;
use App\Model\Order;
use App\Model\Company;
use App\Events\MonthlyInvoice;
use App\Model\BusinessVerification;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Notifications\Notifiable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Notifications\GenerateMonthlyInvoice;

class SendMonthlyInvoiceMail
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
    public function handle(MonthlyInvoice $event)
    {
        $customer = $event->customer;

        // NEED TO RECODE BECAUSE IT WILL GENERATE PREVIOUS INVOICES
        
        $order = Order::where('customer_id', $customer->id)->first();

        $invoice = [
            'start_date' => '2019-01-11',
            'end_date'   => '2019-02-11',
            'due_date'   => '2019-02-10',
            'total_due'  => '23.00',
            'subtotal'   => '435.00',
         ];
         // $invoice = [
         //    'start_date' => $order->invoice->start_date,
         //    'end_date'   => $order->invoice->end_date,
         //    'due_date'   => $order->invoice->due_date,
         //    'total_due'  => $order->invoice->total_due,
         //    'subtotal'   => $order->invoice->subtotal,
         // ];
        $pdf = PDF::loadView('templates/invoice', compact('invoice'))->setPaper('a4', 'landscape')->stream();

        $configurationSet = $this->setMailConfiguration($order);

        if ($configurationSet) {
            return false;
        }


        $customer->notify(new GenerateMonthlyInvoice($order, $pdf));        
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
