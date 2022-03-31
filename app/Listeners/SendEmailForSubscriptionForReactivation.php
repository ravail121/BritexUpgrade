<?php

namespace App\Listeners;

use Notification;
use App\Model\Subscription;
use App\Model\EmailTemplate;
use App\Notifications\SendEmails;
use Illuminate\Notifications\Notifiable;
use App\Support\Configuration\MailConfiguration;

class SendEmailForSubscriptionForReactivation
{
	use Notifiable, MailConfiguration, EmailLayout;

	const STATUS_CODE = [
		'for-restoration'    => 'for-restoration'
	];

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
	    $subscriptionId = $event->subscriptionId;

	    $subscription = Subscription::whereId($subscriptionId)->with('customer', 'plans')->first();
	    $subscription['phone_number'] = $subscription->phoneNumberFormatted;

	    $dataRow = [
		    'subscription' => $subscription,
		    'customer'     =>  $subscription->customer,
		    'plan'         =>  $subscription->plans,
	    ];

		dd($dataRow['customer']);

	    $configurationSet = $this->setMailConfiguration($dataRow['customer']);

	    if ($configurationSet) {
		    return false;
	    }

	    $emailTemplates = EmailTemplate::where('company_id', $dataRow['customer']->company_id)
	                                   ->where('code', self::STATUS_CODE['for-restoration'])
	                                   ->get();

	    foreach ($emailTemplates as $emailTemplate) {
		    $row = $this->makeEmailLayout($emailTemplate, $dataRow['customer'], $dataRow);

		    Notification::route('mail', $row['email'])->notify(new SendEmails($dataRow['customer'], $emailTemplate, $dataRow['customer']->business_verification_id, $row['body'], $row['email']));
		}

    }
}
