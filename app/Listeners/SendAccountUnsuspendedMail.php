<?php

namespace App\Listeners;

use Notification;
use App\Model\Order;
use App\Model\EmailTemplate;
use App\Notifications\SendEmails;
use Illuminate\Notifications\Notifiable;
use App\Support\Configuration\MailConfiguration;
use Illuminate\Support\Facades\Log;


class SendAccountUnsuspendedMail
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
        $customer = $event->customer;

	    $configurationSet = $this->setMailConfigurationById($customer->company_id);

	    Log::info('Configuration Set');
	    Log::info($configurationSet);

        if ($configurationSet) {
            return false;
        }

        $dataRow['customer'] = $customer;

        $emailTemplates = EmailTemplate::where('company_id', $customer->company_id)
        ->where('code', 'account-unsuspended')
        ->get();

        $order = Order::where('customer_id', $customer->id)->first();

        foreach ($emailTemplates as $key => $emailTemplate) {
            $row = $this->makeEmailLayout($emailTemplate, $customer, $dataRow);

            Notification::route('mail', $row['email'])->notify(new SendEmails($order, $emailTemplate, $customer->business_verification_id, $row['body'], $row['email']));
        }
    }
}
