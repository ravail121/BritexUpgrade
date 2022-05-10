<?php

namespace App\Listeners;

use App\Events\SendMailData;
use Notification;
use App\Notifications\SendEmailForInvoices;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notifiable;
use App\Support\Configuration\MailConfiguration;
use Illuminate\Queue\InteractsWithQueue;

class SendMailFired
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
     * @param  \App\Events\SendMailData  $event
     * @return void
     */
    public function handle(SendMailData $event)
    {
        $invoices = $event->invoices;

		/**
		 * @internal Using Company with id 1 for SMTP configuration
		 */
		$companyId = 1;
	    $configurationSet = $this->setMailConfigurationById($companyId);

	    if ($configurationSet) {
		    return false;
	    }

	    //$alertEmails = ['support@britewireless.com', 'shlomo@britewireless.com', 'david@britewireless.com', 'prajwal@britewireless.com'];
		$alertEmails = ['rvlirshad@gmail.com'];

		foreach($alertEmails as $alertEmail){
			Notification::route('mail', $alertEmail)->notify(new SendEmailForInvoices($invoices, $companyId));
		}
    }
}
