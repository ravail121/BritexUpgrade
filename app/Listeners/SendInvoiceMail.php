<?php

namespace App\Listeners;

use Config;
use Notification;
use App\Model\EmailTemplate;
use App\Listeners\EmailLayout;
use Illuminate\Notifications\Notifiable;
use App\Notifications\EmailWithAttachment;
use App\Support\Configuration\MailConfiguration;

class SendInvoiceMail
{
    use Notifiable, MailConfiguration, EmailLayout;

    const STATUS_CODE = [
        'custom-charge'   => 'custom-charge',
    ];
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
        $invoice = $event->invoice;
        $pdf  = $event->pdf;
        $type = $event->type;
        $customer = $invoice->customer;
        $dataRow = [
            'invoice' => $invoice,
            'customer' => $customer,
        ];

        $configurationSet = $this->setMailConfiguration($customer);

        if ($configurationSet) {
            return false;
        }

        $emailTemplates = EmailTemplate::where('company_id', $dataRow['customer']['company_id'])
        ->where('code', self::STATUS_CODE[$type])
        ->get();

        $customer['customer_id'] = $customer->id;
        $customer['staff_id'] = $invoice->staff_id;

        foreach ($emailTemplates as $key => $emailTemplate) {
            $row = $this->makeEmailLayout($emailTemplate, $dataRow['customer'], $dataRow);

            Notification::route('mail', $row['email'])->notify(new EmailWithAttachment($customer, $pdf, $emailTemplate, $customer->business_verification_id, $row['body'], $row['email'], null));
        }
    }
}
