<?php

namespace App\Listeners;

use Illuminate\Notifications\Notifiable;
use Illuminate\Notifications\Notification;
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

	    $alertEmails = ['support@britewireless.com', 'shlomo@britewireless.com', 'david@britewireless.com'];

	    foreach($alertEmails as $alertEmail){
		    Notification::route('mail', $alertEmail)->notify(new SendEmailForNullSubscriptionStartDate($customers));
	    }
    }
}
