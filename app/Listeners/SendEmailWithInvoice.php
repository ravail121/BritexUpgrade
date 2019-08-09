<?php

namespace App\Listeners;

use PDF;
use Mail;
use Config;
use Notification;
use Carbon\Carbon;
use App\Model\Order;
use App\Model\Invoice;
use App\Model\Company;
use App\Model\EmailTemplate;
use App\Events\InvoiceGenerated;
use App\Model\BusinessVerification;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Notifications\Notifiable;
use App\Notifications\EmailWithAttachment;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Model\SystemEmailTemplateDynamicField;
use App\Model\Subscription;
use App\Model\InvoiceItem;
use App\Model\Plan;
use App\Model\Addon;
use App\Model\Device;
use App\Model\Sim;
use App\Model\Customer;
use App\Http\Controllers\Api\V1\CronJobs\InvoiceTrait;

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
        
        $order         = $event->order;
        
        $customerOrder = Order::find($order->id);

        $orderType     = $customerOrder->invoice->type;

        $data          = $order->isOrder($order) ? $this->setOrderInvoiceData($order) : $this->setMonthlyInvoiceData($order);
        
        $invoice       = $this->dataForInvoice($customerOrder->invoice, $customerOrder);

        $invoice       = array_merge($data, $invoice);
        
        if ($orderType  == Invoice::TYPES['one-time']) {
            
            $pdf = PDF::loadView('templates/onetime-invoice', compact('invoice'))->setPaper('letter', 'portrait');

        } elseif ($orderType  == Invoice::TYPES['monthly']) {
           
            $pdf = PDF::loadView('templates/monthly-invoice', compact('invoice'))->setPaper('letter', 'portrait');

        }
        
        
        $configurationSet = $this->setMailConfiguration($order);
        
        if ($configurationSet) {
            
            return false;
        }

        $customer = $order->customer;
        $dataRow['customer'] = $customer;

        $emailTemplate = '';

        if ($order->invoice->type == 2) {
            $emailTemplates      = EmailTemplate::where('company_id', $order->company_id)->where('code', 'one-time-invoice')->get();
        
        } elseif ($order->invoice->type == 1) {
            $emailTemplates      = EmailTemplate::where('company_id', $order->company_id)->where('code', 'monthly-invoice')->get();

        }
        $note = 'Invoice Link- '.route('api.invoice.get').'?order_hash='.$order->hash;

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

            Notification::route('mail', $email)->notify(new EmailWithAttachment($order, $pdf, $emailTemplate, $customer->business_verification_id, $body, $email, $note));
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
