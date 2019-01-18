<?php

namespace App\Listeners;

use Mail;
use Config;
use App\Model\Order;
use App\Model\Company;
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
        $order           = Order::where('hash', $orderHash)->first();

        \Log::info('Setting Mail Configuration');

        $configurationSet = $this->setMailConfiguration($order);

        \Log::info(Config::get('mail'));

        if ($configurationSet) {
            return false;
        }

        \Log::info('Notification Triggering.......');

        $bizVerification->notify(new BizVerificationApproved($order, $bizVerification));        
    }


    /**
     * This method sets the Configuration of the Mail according to the Company
     * 
     * @param Order $order
     * @return boolean
     */
    protected function setMailConfiguration($order)
    {
        $company = Company::find($order->company_id);
        $config = [
            'driver'   => $company->smtp_driver,
            'host'     => $company->smtp_host,
            'port'     => $company->smtp_port,
            'username' => $company->smtp_username,
            'password' => $company->smtp_password,
        ];

        Config::set('mail',$config);
        return false;
    }

}
