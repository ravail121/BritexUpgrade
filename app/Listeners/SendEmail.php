<?php

namespace App\Listeners;

use Mail;
use App\Model\Order;
use App\Model\EmailTemplate;
use App\Notifications\SendEmails;
use App\Model\BusinessVerification;
use Illuminate\Notifications\Notifiable;
use Notification;
use App\Events\BusinessVerificationCreated;
use App\Support\Configuration\MailConfiguration;

class SendEmail
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
     * @param  BusinessVerificationCreated  $event
     * @return void
     */
    public function handle(BusinessVerificationCreated $event)
    {
        $orderHash    = $event->orderHash;
        $businessHash = $event->bizHash;

        $order = Order::hash($orderHash)->first();
        
        $businessVerification = BusinessVerification::hash($businessHash)->first();
        $dataRow['business_verification'] = $businessVerification;


        $emailTemplates = EmailTemplate::where('company_id', $order->company_id)->where('code', 'biz-verification-submitted')->get();

        foreach ($emailTemplates as $key => $emailTemplate) {
            $row = $this->makeEmailLayout($emailTemplate, $businessVerification, $dataRow);
	        $configurationSet = $this->setMailConfiguration($order);

	        if ($configurationSet) {
		        return false;
	        }

            Notification::route('mail', $row['email'])->notify(new SendEmails($order, $emailTemplate, $businessVerification->id, $row['body'], $row['email']));
        }
   
    }

}
