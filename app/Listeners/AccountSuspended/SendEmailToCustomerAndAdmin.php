<?php

namespace App\Listeners\AccountSuspended;

use Mail;
use Config;
use Notification;
use App\Model\Order;
use App\Model\Company;
use App\Model\EmailTemplate;
use App\Events\AccountSuspended;
use App\Notifications\SendEmails;
use App\Model\BusinessVerification;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Notifications\Notifiable;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Model\SystemEmailTemplateDynamicField;

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

        $customerTemplates = EmailTemplate::where('company_id', $order->company_id)->where('code', 'account-suspension-customer')->get();
        
        $bizVerification = BusinessVerification::find($order->customer->business_verification_id);

        $templateVales  = SystemEmailTemplateDynamicField::where('code', 'account-suspension-customer')->get()->toArray();

        $adminTemplates = EmailTemplate::where('company_id', $order->company_id)->where('code', 'account-suspension-admin')->get();

        foreach ($customerTemplates as $key => $customerTemplate) {
            $customer->notify(new SendEmail($order, $customerTemplate, $bizVerification, $templateVales));   
        }
        
        foreach ($adminTemplates as $key => $adminTemplate) {
            Notification::route('mail', $adminTemplate->to)->notify(new SendEmails($order, $adminTemplate, $bizVerification, $templateVales));        
        }
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
