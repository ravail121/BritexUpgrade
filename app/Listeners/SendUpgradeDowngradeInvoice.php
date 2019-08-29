<?php

namespace App\Listeners;

use Notification;
use App\Model\Order;
use App\Model\Subscription;
use App\Model\EmailTemplate;
use App\Listeners\EmailLayout;
use Illuminate\Notifications\Notifiable;
use Illuminate\Queue\InteractsWithQueue;
use App\Notifications\EmailWithAttachment;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Support\Configuration\MailConfiguration;

class SendUpgradeDowngradeInvoice
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
     * @param  object  $event
     * @return void
     */
    public function handle($event)
    {
        $order = Order::where([
            'id' => $event->order->id,
        ])->with('customer', 'invoice', 'allOrderGroup')->first();

        $pdf           = $event->pdf;
        $customer = $order->customer;
        $configurationSet = $this->setMailConfiguration($customer);

        if ($configurationSet) {
            return false;
        }

        $subscription = Subscription::where([
            'id' => $order->allOrderGroup->first()->subscription_id,
        ])->with('oldPlan', 'newPlanDetail', 'plans')->first();

        $dataRow = [
            'customer'     => $order->customer,
            'order'        => $order,
            'invoice'      => $order->invoice,
            'subscription' => $subscription,
        ];

        $emailTemplates = EmailTemplate::where('company_id', $customer->company_id)
        ->where('code', 'subscription-change')
        ->get();

        $note = 'Invoice Link';

        if($subscription->upgrade_downgrade_status =="for-upgrade"){
            $subscriptionsChanged = '<p>'.$subscription->phone_number.' Upgraded from  '.$subscription->plans->name.' to '.$subscription->oldPlan->name.' plan</p>';
        }else{
            $subscriptionsChanged = '<p>'.$subscription->phone_number.' Downgrade from  '.$subscription->plans->name.' to '.$subscription->newPlanDetail->name.'  plan</p>';
        }


        foreach ($emailTemplates as $key => $emailTemplate) {
            $row = $this->makeEmailLayout($emailTemplate, $customer, $dataRow);

            $row['body'] = $this->addFieldsToBody('[subscriptions_changed]', $subscriptionsChanged, $row['body']);
            
            Notification::route('mail', $row['email'])->notify(new EmailWithAttachment($order, $pdf, $emailTemplate, $customer->business_verification_id, $row['body'], $row['email'], $note));
        }
    }
}
