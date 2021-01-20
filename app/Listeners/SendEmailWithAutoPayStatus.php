<?php

namespace App\Listeners;

use App\Model\Invoice;
use App\Model\EmailTemplate;
use App\Events\AutoPayStatus;
use App\Notifications\SendEmails;
use Illuminate\Notifications\Notifiable;
use Notification;
use App\Support\Configuration\MailConfiguration;

class SendEmailWithAutoPayStatus
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
    public function handle(AutoPayStatus $event)
    {
        $customer = $event->customer;

        $configurationSet = $this->setMailConfiguration($customer);

        if ($configurationSet) {
            return false;
        }
        $invoice = Invoice::where([['customer_id', $customer->id], ['status', Invoice::INVOICESTATUS['open'] ],['type', Invoice::TYPES['monthly']]])->first();
        $amount = $invoice ? $invoice->subtotal : 0;

        $dataRow['customer'] = $customer;

        $emailTemplates = EmailTemplate::where('company_id', $customer->company_id)
        ->where('code', $customer->auto_pay == '1' ? 'auto-pay-enabled' : 'auto-pay-disabled')
        ->get();

        $customer['customer_id'] = $customer->id;

        foreach ($emailTemplates as $key => $emailTemplate) {
            $row = $this->makeEmailLayout($emailTemplate, $customer, $dataRow);

            $row['body'] = $this->addFieldsToBody('[total_amount_due]', $amount, $row['body']);

            Notification::route('mail', $row['email'])->notify(new SendEmails($customer, $emailTemplate, $customer->business_verification_id, $row['body'], $row['email']));
        }
    }
}
