<?php

namespace App\Listeners;

use App\Events\AutoPayStatus;
use Illuminate\Notifications\Notifiable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Support\Configuration\MailConfiguration;

class SendEmailWithAutoPayStatus
{

    use Notifiable, MailConfiguration;
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
     * @param  object  $event
     * @return void
     */
    public function handle(AutoPayStatus $event)
    {
        $customer = $event->customer;

        $configurationSet = $this->setMailConfiguration($customer);

        if ($configurationSet) {
            return false;
        }

        $dataRow['customer'] = $customer;

        // $emailTemplates = EmailTemplate::where('company_id', $customer->company_id)
        // ->where('code', 'auto-pay-enabled')
        // ->get();

        // $emailTemplates = EmailTemplate::where('company_id', $customer->company_id)
        // ->where('code', 'auto-pay-disabled')
        // ->get();

        // foreach ($emailTemplates as $key => $emailTemplate) {
        //     if(filter_var($emailTemplate->to, FILTER_VALIDATE_EMAIL)){
        //         $email = $emailTemplate->to;
        //     }else{
        //         $email = $businessVerification->email;
        //     }

        //     $names = array();
        //     $column = preg_match_all('/\[(.*?)\]/s', $emailTemplate->body, $names);
        //     $table = null;
        //     $replaceWith = null;

        //     foreach ($names[1] as $key => $name) {
        //         $dynamicField = explode("__",$name);
        //         if($table != $dynamicField[0]){
        //             if(isset($dataRow[$dynamicField[0]])){
        //                 $data = $dataRow[$dynamicField[0]]; 
        //                 $table = $dynamicField[0];
        //             }else{
        //                 unset($names[0][$key]);
        //                 continue;
        //             }
        //         }
        //         $replaceWith[$key] = $data->{$dynamicField[1]} ?: $names[0][$key];
        //     }

        //     $body = $emailTemplate->body($names[0], $replaceWith);

        //     Notification::route('mail', $email)->notify(new SendEmails($order, $emailTemplate, $businessVerification->id, $body, $email));
        }
    }
}
