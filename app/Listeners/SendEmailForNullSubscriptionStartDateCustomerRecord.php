<?php

namespace App\Listeners;

use Config;
use Notification;
use Illuminate\Notifications\Notifiable;
use App\Support\Configuration\MailConfiguration;
use App\Notifications\SendEmailForNullSubscriptionStartDate;

class SendEmailForNullSubscriptionStartDateCustomerRecord
{
	use EmailLayout, Notifiable, MailConfiguration;
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
		$customers = $event->customers;

		/**
		 * @internal Using Company with id 1 for SMTP configuration
		 */
	    $configurationSet = $this->setMailConfigurationById(1);

	    if ($configurationSet) {
		    return false;
	    }

	    $alertEmails = ['support@britewireless.com', 'shlomo@britewireless.com', 'david@britewireless.com', 'prajwal@britewireless.com'];

		foreach($alertEmails as $alertEmail){
			Notification::route('mail', $alertEmail)->notify(new SendEmailForNullSubscriptionStartDate($customers));
		}
	}
}
