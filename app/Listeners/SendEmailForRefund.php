<?php

namespace App\Listeners;

use Notification;
use App\Model\Order;
use App\Model\Customer;
use App\Model\EmailTemplate;
use App\Listeners\EmailLayout;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Notifications\Notifiable;
use App\Notifications\EmailWithAttachment;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Support\Configuration\MailConfiguration;

class SendEmailForRefund
{
    use Notifiable, MailConfiguration, EmailLayout;
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
     * @param  object  $event
     * @return void
     */
    public function handle($event)
    {
        $paymentLog = $event->paymentLog;
        $amount = $event->amount;
        $customer = Customer::find($paymentLog->customer_id);
        $pdf = $event->pdf;
        $note = 'Invoice Link- '.route('api.invoice.download', $customer->company_id).'?invoice_hash='.md5($paymentLog->invoice_id);

        $dataRow = [
            'payment_log' => $paymentLog,
            'customer' => $customer
        ];

        $configurationSet = $this->setMailConfiguration($customer);

        if ($configurationSet) {
            return false;
        }

        $emailTemplates = EmailTemplate::where('company_id', $customer->company_id)
        ->where('code', 'refund')
        ->get();

        $order = Order::where('customer_id', $customer->id)->first();

        foreach ($emailTemplates as $key => $emailTemplate) {
            $row = $this->makeEmailLayout($emailTemplate, $customer, $dataRow);

            $row['body'] = $this->addFieldsToBody('[refund_amount]', $amount, $row['body']);

            Notification::route('mail', $row['email'])->notify(new EmailWithAttachment($order, $pdf, $emailTemplate, $customer->business_verification_id, $row['body'], $row['email'], $note));
        }
    }
}
