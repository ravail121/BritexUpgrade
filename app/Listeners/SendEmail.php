<?php

namespace App\Listeners;


use Mail;
use App\Model\Order;
use App\Model\BusinessVerification;
use App\Notifications\BizVerification;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Notifications\Notifiable;
use Illuminate\Notifications\Notification;
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

        $order                = Order::hash($orderHash)->first();
        $businessVerification = BusinessVerification::hash($businessHash)->first();

        $configurationSet = $this->setMailConfiguration($order);

        if ($configurationSet) {
            return false;
        }

        $businessVerification->notify(new BizVerification($order, $businessVerification));
   
    }

}
