<?php

namespace App\Listeners;

use Notification;
use App\Model\Order;
use App\Model\Customer;
use App\Model\EmailTemplate;
use App\Notifications\SendEmails;
use Illuminate\Support\Facades\Log;
use App\Events\FailToAutoPaidInvoice;
use Illuminate\Notifications\Notifiable;

use App\Support\Configuration\MailConfiguration;

class SendEmailToRemindFailToAutoPaidInvoice
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
    public function handle(FailToAutoPaidInvoice $event)
    {
        $customers = $event->customer;
        $customer = Customer::find($customers['id']);

	    $configurationSet = $this->setMailConfigurationById($customer->company_id);

	    Log::info('SendEmailToRemindFailToAutoPaidInvoice Configuration Set');
	    Log::info($configurationSet);


	    if ($configurationSet) {
            return false;
        }

        $dataRow['customer'] = $customer;

        $emailTemplates = EmailTemplate::where('company_id', $customer->company_id)
        ->where('code', 'auto-pay-fail')
        ->get();

        $order = Order::where('customer_id', $customer->id)->first();

        foreach ($emailTemplates as $key => $emailTemplate) {
            $row = $this->makeEmailLayout($emailTemplate, $customer, $dataRow);

            $row['body'] = $this->addFieldsToBody(['[total_amount_due]', '[description]'], [$customers['mounthlyInvoice']['subtotal'], $event->description], $row['body']);

            Notification::route('mail', $row['email'])->notify(new SendEmails($order, $emailTemplate, $customer->business_verification_id, $row['body'], $row['email']));
        }
    }
}
