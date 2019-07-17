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

        $customer = $order->customer;
        $dataRow['customer'] = $customer; 

        $templateVales  = SystemEmailTemplateDynamicField::where('code', 'account-suspension-customer')->get()->toArray();

        $adminTemplates = EmailTemplate::where('company_id', $order->company_id)->where('code', 'account-suspension-admin')->get();

        $names = array_column($templateVales, 'name');
        $column = array_column($templateVales, 'format_name');

        $table = null;

        foreach ($names as $key => $name) {
            $dynamicField = explode("__",$name);
            if($table != $dynamicField[0]){
                $data = $dataRow[$dynamicField[0]]; 
                $table = $dynamicField[0];
            }
            $replaceWith[$key] = $data->{$dynamicField[1]};
        }

        foreach ($customerTemplates as $key => $customerTemplate) {
            if(filter_var($customerTemplate->to, FILTER_VALIDATE_EMAIL)){
                $email = $customerTemplate->to;
            }else{
                $email = $customer->email;
            }
            $body = $customerTemplate->body($column, $replaceWith);
            Notification::route('mail', $email)->notify(new SendEmails($order, $customerTemplate, $customer->business_verification_id, $body, $email));  
        }
        
        foreach ($adminTemplates as $key => $adminTemplate) {
            $body = $adminTemplate->body($column, $replaceWith);
            Notification::route('mail', $adminTemplate->to)->notify(new SendEmails($order, $customerTemplate, $customer->business_verification_id, $body, $email));      
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
