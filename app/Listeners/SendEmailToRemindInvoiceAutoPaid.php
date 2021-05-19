<?php

namespace App\Listeners;

use App\Model\Order;
use App\Model\Customer;
use App\Model\EmailTemplate;
use App\Events\InvoiceAutoPaid;
use App\Notifications\SendEmails;
use Illuminate\Notifications\Notifiable;
use Notification;
use App\Support\Configuration\MailConfiguration;

class SendEmailToRemindInvoiceAutoPaid
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
    public function handle(InvoiceAutoPaid $event)
    {
        $customers = $event->customer;
        $customer = Customer::find($customers['id']);

        $dataRow['customer'] = $customer;
        $dataRow['invoice'] = $customers['mounthlyInvoice'];

        $emailTemplates = EmailTemplate::where('company_id', $customer['company_id'])
        ->where('code', 'auto-pay-success')
        ->get();

        $order = Order::where('customer_id', $customer['id'])->first();

        foreach ($emailTemplates as $key => $emailTemplate) {
            $row = $this->makeEmailLayout($emailTemplate, $customer, $dataRow);

            $row['body'] = $this->addFieldsToBody(
                ['[total_amount_due]', '[invoice__start_date]', '[invoice__end_date]'],
                [$customers['mounthlyInvoice']['total_due'], $customers['mounthlyInvoice']['start_date'], $customers['mounthlyInvoice']['end_date']],
                $row['body']
            );

	        $configurationSet = $this->setMailConfiguration($customer);

	        if ($configurationSet) {
		        return false;
	        }

	        Notification::route('mail', $row['email'])->notify(new SendEmails($order, $emailTemplate, $customer->business_verification_id, $row['body'], $row['email']));
        }
    }
}
