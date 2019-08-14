<?php

namespace App\Listeners;

use Notification;
use App\Events\PortPending;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Model\Port;
use App\Model\EmailTemplate;
use App\Support\Configuration\MailConfiguration;
use Illuminate\Notifications\Notifiable;
use App\Notifications\SendEmails;
use App\Listeners\EmailLayout;

class SendPortStatusMail
{
    use Notifiable, MailConfiguration, EmailLayout;
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
     * @param  PortPending  $event
     * @return void
     */
    public function handle(PortPending $event)
    {   
        $port           = Port::find($event->portId);
        $subscription   = $port->subscription;
        $order          = $subscription->order;
        $customer       = $subscription->customerRelation;
        $emailTemplates = EmailTemplate::where('company_id', $customer->company_id)->where('code', 'port-pending')->get();
        $configurationSet = $this->setMailConfiguration($customer);

        if ($configurationSet) {
            return false;
        }

        $dataRow['customer']        = $customer;
        $dataRow['port']            = $port;
        $dataRow['subscription']    = $subscription;

        foreach ($emailTemplates as $key => $emailTemplate) {
            $row = $this->makeEmailLayout($emailTemplate, $customer, $dataRow);

            Notification::route('mail', $row['email'])->notify(new SendEmails($order, $emailTemplate, $customer->business_verification_id, $row['body'], $row['email']));
        }

    }
}
