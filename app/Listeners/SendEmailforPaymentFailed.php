<?php

namespace App\Listeners;

use App\Model\EmailTemplate;
use App\Notifications\SendEmails;
use Illuminate\Notifications\Notifiable;
use Notification;
use App\Support\Configuration\MailConfiguration;

class SendEmailforPaymentFailed
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


		$dataRow['customer'] = $customer;
		$customer['customer_id'] = $customer->id;

		$emailTemplates = EmailTemplate::where('company_id', $customer->company_id)
		                               ->where('code', 'payment-failed')
		                               ->get();

		foreach ($emailTemplates as $key => $emailTemplate) {
			$row = $this->makeEmailLayout($emailTemplate, $customer, $dataRow);

			$configurationSet = $this->setMailConfiguration($customer);

			if ($configurationSet) {
				return false;
			}

			Notification::route('mail', $customer->company->support_email)->notify(new SendEmails($customer , $emailTemplate, $customer->business_verification_id, $row['body'], $customer->company->support_email));
		}
	}
}
