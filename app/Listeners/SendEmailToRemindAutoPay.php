<?php

namespace App\Listeners;

use Notification;
use App\Model\Order;
use App\Model\EmailTemplate;
use App\Listeners\EmailLayout;
use App\Events\AutoPayReminder;
use App\Notifications\SendEmails;
use Illuminate\Notifications\Notifiable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Support\Configuration\MailConfiguration;

class SendEmailToRemindAutoPay
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
    public function handle(AutoPayReminder $event)
    {
        $customer = $event->customer;
        $invoice = $event->invoice;
        $configurationSet = $this->setMailConfiguration($customer);

        if ($configurationSet) {
            return false;
        }

        $dataRow = [
            'customer' => $customer,
            'invoice'  => $invoice
        ];

        $emailTemplates = EmailTemplate::where('company_id', $customer->company_id)
        ->where('code', 'auto-pay-reminder')
        ->get();

        $order = Order::where('customer_id', $customer->id)->first();

        foreach ($emailTemplates as $key => $emailTemplate) {
            $row = $this->makeEmailLayout($emailTemplate, $customer, $dataRow);

            $row['body'] = $this->addFieldsToBody('[total_amount_due]', $invoice->subtotal, $row['body']);

            Notification::route('mail', $row['email'])->notify(new SendEmails($order, $emailTemplate, $customer->business_verification_id, $row['body'], $row['email']));
        }
    }
}
