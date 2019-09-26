<?php

namespace App\Listeners;

use PDF;
use Mail;
use Config;
use Notification;
use App\Model\Order;
use App\Model\Company;
use App\Model\EmailTemplate;
use App\Events\MonthlyInvoice;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Notifications\Notifiable;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Notifications\GenerateMonthlyInvoice;
use App\Model\SystemEmailTemplateDynamicField;

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
        
        $order = Order::where('customer_id', $customer->id)->with('customer')->first();

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
        $pdf = PDF::loadView('templates/monthly-invoice', compact('invoice'))->setPaper('letter', 'portrait');

        $configurationSet = $this->setMailConfiguration($order);

        if ($configurationSet) {
            return false;
        }

        $emailTemplates = EmailTemplate::where('company_id', $this->order->company_id)->where('code', 'monthly-invoice')->get();

        $templateVales  = SystemEmailTemplateDynamicField::where('code', 'one-time-invoice')->get()->toArray();

        $note = 'Invoice Link '.route('api.invoice.get').'?order_hash='.$this->order->hash;

        foreach ($emailTemplates as $key => $emailTemplate) {
            if(filter_var($emailTemplate->to, FILTER_VALIDATE_EMAIL)){
                $email = $emailTemplate->to;
            }else{
                $email = $customer->email;
            }
            Notification::route('mail', $email)->notify(new EmailWithAttachment($order, $pdf, $emailTemplate, $$this->order->customer->business_verification_id, $templateVales, $note));
        }          
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
