<?php

namespace App\Listeners;

use Mail;
use Config;
use App\Model\Customer;
use App\Model\Company;
use App\Events\ForgotPassword;
use App\Notifications\EmailWithHash;
use Illuminate\Notifications\Notifiable;
/**
 * Class SendEmailWithPasswordHash
 *
 * @package App\Listeners
 */
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
	 * @param ForgotPassword $event
	 *
	 * @return false
	 */
    public function handle(ForgotPassword $event)
    {
        $user = $event->user;

        $customer = Customer::where([
            'email'         => $user['email'],
            'company_id'    => $user['company_id'],
        ])->first();

        $configurationSet = $this->setMailConfiguration($customer['company_id']);

        if ($configurationSet) {
            return false;
        }
        
        $customer->notify(new EmailWithHash($user));        
    }


	/**
	 * @param $companyId
	 *
	 * @return false
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
