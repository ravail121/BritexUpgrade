<?php

namespace App\Listeners;

use Mail;
use Config;
use App\Model\Customer;
use App\Model\Company;
use App\Events\ForgotPassword;
use App\Model\BusinessVerification;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Notifications\Notifiable;
use Illuminate\Notifications\Notification;
use App\Notifications\EmailWithHash;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendEmailWithPasswordHash
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
    public function handle(ForgotPassword $event)
    {
        $user = $event->user;

        $configurationSet = $this->setMailConfiguration();

        if ($configurationSet) {
            return false;
        }
        $customer = Customer::where('email', $user['email'])->first();
        $customer->notify(new EmailWithHash($user));        
    }


    /**
     * This method sets the Configuration of the Mail according to the Company
     * 
     * @param Order $order
     * @return boolean
     */
    protected function setMailConfiguration()
    {
        $company = Company::find(1);
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
