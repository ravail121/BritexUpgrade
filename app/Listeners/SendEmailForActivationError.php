<?php

namespace App\Listeners;

use Notification;
use App\Model\Order;
use App\Model\EmailTemplate;
use App\Notifications\SendEmails;
use Illuminate\Notifications\Notifiable;
use App\Support\Configuration\MailConfiguration;

class SendEmailForActivationError
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
	    $subscription = $event->subscription;
		$message = $event->message;

	    $emailTemplates = EmailTemplate::where('company_id', $subscription->company_id)
	                                   ->where('code', 'activation-error')
	                                   ->get();

	    $customer = $subscription->customer;
	    $order = $subscription->order;

	    $dataRow = [
		    'subscription'  => $subscription,
		    'customer'      =>  $customer,
		    'plan'          =>  $subscription->plans,
	    ];


	    foreach ($emailTemplates as $emailTemplate) {
		    $row = $this->makeEmailLayout($emailTemplate, $customer, $dataRow);

		    $row['body'] = $this->addFieldsToBody('[error_message]', $message, $row['body']);

		    $configurationSet = $this->setMailConfiguration($customer);

		    if ($configurationSet) {
			    return false;
		    }

		    Notification::route('mail', $row['email'])->notify(new SendEmails($order, $emailTemplate, $customer->business_verification_id, $row['body'], $row['email']));
	    }

    }
}
