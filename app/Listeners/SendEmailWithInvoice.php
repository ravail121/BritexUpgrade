<?php

namespace App\Listeners;

use Config;
use Notification;
use Carbon\Carbon;
use App\Model\Order;
use App\Model\Company;
use App\Model\EmailTemplate;
use App\Events\InvoiceGenerated;
use Illuminate\Notifications\Notifiable;
use App\Notifications\EmailWithAttachment;
use App\Http\Controllers\Api\V1\Traits\InvoiceTrait;

class SendEmailWithInvoice
{
    use InvoiceTrait;
    const SIM_TYPE    = 'sim';
    const PLAN_TYPE   = 'plan';
    const ADDON_TYPE  = 'addon';
    const DEVICE_TYPE = 'device';
    const DESCRIPTION = 'Activation Fee';
    const SHIPPING    = 'Shipping Fee';
    const ONETIME     = 3;
    const TAXES       = 7;
    const COUPONS     = 6;

    use Notifiable;

    /**
     * Date-Time variable
     * 
     * @var $carbon
     */
    public $carbon;



    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct(Carbon $carbon)
    {
        $this->carbon = $carbon;
    }

    /**
     * Handle the event.
     *
     * @param  BusinessVerificationCreated  $event
     * @return void
     */
    public function handle(InvoiceGenerated $event)
    {

        $customerOrder = Order::find($event->order->id);
        $pdf           = $event->pdf;
        
        $configurationSet = $this->setMailConfiguration($customerOrder);
        
        if ($configurationSet) {
            
            return false;
        }

        $customer = $customerOrder->customer;
        $dataRow['customer'] = $customer;

        $emailTemplate = '';

        if ($customerOrder->invoice->type == 2) {
            $emailTemplates      = EmailTemplate::where('company_id', $customerOrder->company_id)->where('code', 'one-time-invoice')->get();
        
        } elseif ($customerOrder->invoice->type == 1) {
            $emailTemplates      = EmailTemplate::where('company_id', $customerOrder->company_id)->where('code', 'monthly-invoice')->get();

        }
        $note = 'Invoice Link- '.route('api.invoice.download', $customerOrder->company_id).'?order_hash='.$customerOrder->hash;

        foreach ($emailTemplates as $key => $emailTemplate) {
            if(filter_var($emailTemplate->to, FILTER_VALIDATE_EMAIL)){
                $email = $emailTemplate->to;
            }else{
                $email = $customer->email;
            }

            $names = array();
            $column = preg_match_all('/\[(.*?)\]/s', $emailTemplate->body, $names);
            $table = null;
            $replaceWith = null;

            foreach ($names[1] as $key => $name) {
                $dynamicField = explode("__",$name);
                if($table != $dynamicField[0]){
                    if(isset($dataRow[$dynamicField[0]])){
                        $data = $dataRow[$dynamicField[0]]; 
                        $table = $dynamicField[0];
                    }else{
                        unset($names[0][$key]);
                        continue;
                    }
                }
                $replaceWith[$key] = $data->{$dynamicField[1]} ?: $names[0][$key];
            }

            $body = $emailTemplate->body($names[0], $replaceWith);

            Notification::route('mail', $email)->notify(new EmailWithAttachment($customerOrder, $pdf, $emailTemplate, $customer->business_verification_id, $body, $email, $note));
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
        
        Config::set('mail',$config);
        return false;
    }

    
}
