<?php

namespace App\Listeners;


use Carbon\Carbon;
use App\Model\Order;
use App\Model\EmailTemplate;
use Illuminate\Notifications\Notifiable;
use Notification;
use App\Notifications\EmailWithAttachment;
use App\Support\Configuration\MailConfiguration;

class SendEmailWithInvoice
{
    use Notifiable, MailConfiguration, EmailLayout;

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
    public function handle($event)
    {

        $customerOrder = Order::whereId($event->order->id)->with('customer', 'invoice')->first();
        $pdf           = $event->pdf;
        
        $configurationSet = $this->setMailConfiguration($customerOrder);
        
        if ($configurationSet) {
            
            return false;
        }

        $customer = $customerOrder->customer;
        $dataRow = [
            'customer' =>  $customer,
            'order'    =>  $customerOrder,
            'invoice'  =>  $customerOrder->invoice
        ];

        if ($customerOrder->invoice->type == 2) {
            $emailTemplates      = EmailTemplate::where('company_id', $customerOrder->company_id)->where('code', 'one-time-invoice')->get();
        
        } elseif ($customerOrder->invoice->type == 1) {
            $emailTemplates      = EmailTemplate::where('company_id', $customerOrder->company_id)->where('code', 'monthly-invoice')->get();

        }
        $note = 'Invoice Link- '.route('api.invoice.download', $customerOrder->company_id).'?order_hash='.$customerOrder->hash;

        foreach ($emailTemplates as $key => $emailTemplate) {
            $row = $this->makeEmailLayout($emailTemplate, $customer, $dataRow);
            
            Notification::route('mail', $row['email'])->notify(new EmailWithAttachment($customerOrder, $pdf, $emailTemplate, $customer->business_verification_id, $row['body'], $row['email'], $note));
        }        
    }
}
