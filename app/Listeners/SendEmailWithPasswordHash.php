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

        $customer = Customer::where([
            'email' => $user['email'],
            'company_id' => $user['company_id'],
        ])->first();

        $configurationSet = $this->setMailConfiguration($customer['company_id']);

        if ($configurationSet) {
            return false;
        }
        
        $customer->notify(new EmailWithHash($user));        
    }


    /**
     * This method sets the Configuration of the Mail according to the Company
     * 
     * @param Order $order
     * @return boolean
     */
    protected function setMailConfiguration($companyId)
    {
        $company = Company::find($companyId);
        $config = [
            'driver'   => $company->smtp_driver,
            'host'     => $company->smtp_host,
            'port'     => $company->smtp_port,
            'username' => $company->smtp_username,
            'password' => $company->smtp_password,
            'encryption' => $company->smtp_encryption,
        ];

        Config::set('mail',$config);
        return false;
    }

}
