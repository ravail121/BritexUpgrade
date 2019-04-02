<?php

namespace App\Listeners\AccountSuspended;

use Mail;
use Config;
use Notification;
use App\Model\Order;
use App\Model\Company;
use App\Model\EmailTemplate;
use App\Events\AccountSuspended;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Notifications\Notifiable;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Notifications\AccountSuspended\InformAdminAccountSuspended;
use App\Notifications\AccountSuspended\InformCustomerAccountSuspended;

class SendEmailToCustomerAndAdmin
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
    public function handle(AccountSuspended $event)
    {
        $customer = $event->customer;
        
        $order = Order::where('customer_id', $customer->id)->first();

        $config = $this->setMailConfiguration($order);

        if (!count($config)) {
            return false;
        }

        $email = EmailTemplate::where('company_id', $order->company_id)->where('code', 'account-suspension-admin')->first();


        $customer->notify(new InformCustomerAccountSuspended($order));

        Notification::route('mail', $email->from)->notify(new InformAdminAccountSuspended($order));        
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

        Config::set('mail', $config);

        return $config;
    }

}
