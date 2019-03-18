<?php

namespace App\Listeners;


use Mail;
use Notification;
use App\Model\Order;
use App\Model\EmailTemplate;
use App\Model\BusinessVerification;
use App\Notifications\BizVerification;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Notifications\Notifiable;
use App\Events\BusinessVerificationCreated;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Support\Configuration\MailConfiguration;

class SendEmail
{
    use Notifiable, MailConfiguration;

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

        $email = EmailTemplate::where('company_id', $order->company_id)->where('code', 'biz-verification-submitted')->first();

        $businessVerification = BusinessVerification::hash($businessHash)->first();

        $configurationSet = $this->setMailConfiguration($order);

        if ($configurationSet) {
            return false;
        }

        Notification::route('mail', $email->to)->notify(new BizVerification($order, $businessVerification));  
   
    }

}
