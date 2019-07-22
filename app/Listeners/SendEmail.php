<?php

namespace App\Listeners;

use Mail;
use Notification;
use App\Model\Order;
use App\Model\EmailTemplate;
use App\Notifications\SendEmails;
use App\Model\BusinessVerification;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Notifications\Notifiable;
use App\Events\BusinessVerificationCreated;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Support\Configuration\MailConfiguration;

class SendEmail
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
     * @param  BusinessVerificationCreated  $event
     * @return void
     */
    public function handle(BusinessVerificationCreated $event)
    {
        $orderHash    = $event->orderHash;
        $businessHash = $event->bizHash;

        $order = Order::hash($orderHash)->first();
        
        $businessVerification = BusinessVerification::hash($businessHash)->first();
        $dataRow['business_verification'] = $businessVerification;

        $configurationSet = $this->setMailConfiguration($order);

        if ($configurationSet) {
            return false;
        }

        $emailTemplates = EmailTemplate::where('company_id', $order->company_id)->where('code', 'biz-verification-submitted')->get();

        foreach ($emailTemplates as $key => $emailTemplate) {
            if(filter_var($emailTemplate->to, FILTER_VALIDATE_EMAIL)){
                $email = $emailTemplate->to;
            }else{
                $email = $businessVerification->email;
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

            Notification::route('mail', $email)->notify(new SendEmails($order, $emailTemplate, $businessVerification->id, $body, $email));
        }
   
    }

}
