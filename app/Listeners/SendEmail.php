<?php

namespace App\Listeners;


use Mail;

//use App\Notifications\InvoicePaid;
use App\Model\BusinessVerification;
use App\Notifications\BizVerification;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Notifications\Notifiable;
use Illuminate\Notifications\Notification;
use App\Events\BusinessVerificationCreated;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendEmail
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
    public function handle(BusinessVerificationCreated $event)
    {
        
        $orderHash    = $event->orderHash;
        $businessHash = $event->bizHash;

        $businessVerification = BusinessVerification::where('hash', $businessHash) -> first();
        $businessVerification->notify(new BizVerification($orderHash,$businessHash));
        //Notification::send($BusinessVerification, new BizVerification($orderHash,$businessHash));
        

   
    }

}
