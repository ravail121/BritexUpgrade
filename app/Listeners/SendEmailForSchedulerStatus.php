<?php

namespace App\Listeners;

use Config;
use Notification;
use Illuminate\Notifications\Notifiable;
use App\Support\Configuration\MailConfiguration;
use App\Notifications\EmailForSchedulerStatus;

class SendEmailForSchedulerStatus
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
		$cronEntries = $event->cronEntries;

		/**
		 * @internal Using Company with id 1 for SMTP configuration
		 */
		$companyId = 1;
		$configurationSet = $this->setMailConfigurationById($companyId);

		if ($configurationSet) {
			return false;
		}

		$alertEmails = ['support@britewireless.com', 'shlomo@britewireless.com', 'david@britewireless.com', 'prajwal@britewireless.com'];

		foreach($alertEmails as $alertEmail){
			Notification::route('mail', $alertEmail)->notify(new EmailForSchedulerStatus($cronEntries, $companyId));
		}
	}
}
