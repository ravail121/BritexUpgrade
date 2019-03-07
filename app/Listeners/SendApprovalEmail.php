<?php

namespace App\Listeners;

use Mail;
use App\Model\Order;
use App\Model\BusinessVerification;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Notifications\Notifiable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Events\BusinessVerificationApproved;
use App\Notifications\BizVerificationApproved;
use App\Support\Configuration\MailConfiguration;

class SendApprovalEmail
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
    public function handle(BusinessVerificationApproved $event)
    {
        $bizVerification = $event->bizVerification;
        $order           = Order::find($bizVerification->order_id);

        $configurationSet = $this->setMailConfiguration($order);

        if ($configurationSet) {
            return false;
        }

        $bizVerification->notify(new BizVerificationApproved($order, $bizVerification));        
    }

}
