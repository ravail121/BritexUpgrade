<?php

namespace App\Listeners;


use Notification;
use App\Model\EmailTemplate;
use App\Notifications\SendEmails;
use Illuminate\Notifications\Notifiable;
use App\Support\Configuration\MailConfiguration;

class SendEmailForCreditCardExpirationReminder
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
	    $customerCreditCard = $event->customerCreditCard;
	    $customer = $customerCreditCard->customer;

	    $dataRow = [
		    'customer_credit_card'  => $customerCreditCard,
		    'customer'              => $customer
	    ];

	    $emailTemplates = EmailTemplate::where('company_id', $customerCreditCard->customer->company_id)
	                                   ->where('code', 'credit-card-expiration')
	                                   ->get();

	    foreach ($emailTemplates as $emailTemplate) {
		    $row = $this->makeEmailLayout($emailTemplate, $customer, $dataRow);

		    $configurationSet = $this->setMailConfiguration($customer);

		    if ($configurationSet) {
			    return false;
		    }

		    Notification::route('mail', $customer->company->support_email)->notify(new SendEmails($customer , $emailTemplate, $customer->business_verification_id, $row['body'], $customer->company->support_email));
	    }
    }
}
