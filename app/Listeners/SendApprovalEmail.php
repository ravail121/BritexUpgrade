<?php

namespace App\Listeners;

use Mail;
use App\Model\BusinessVerification;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Notifications\Notifiable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Events\BusinessVerificationApproved;
use App\Notifications\BizVerificationApproved;

class SendApprovalEmail
{
    use Notifiable;

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
        $orderHash       = $event->orderHash;
        $bizVerification = $event->bizVerification;

        // $businessVerification = BusinessVerification::where('hash', $businessHash)->first();
        $bizVerification->notify(new BizVerificationApproved($orderHash, $bizVerification));        
    }

}
