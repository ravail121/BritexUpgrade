<?php

namespace App\Listeners;

use App\Model\Order;
use App\Model\Subscription;
use App\Model\EmailTemplate;
use Illuminate\Notifications\Notifiable;
use Notification;
use App\Notifications\EmailWithAttachment;
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


        $subscription = Subscription::where([
            'id' => $order->allOrderGroup->first()->subscription_id,
        ])->with('oldPlan', 'newPlanDetail', 'plans')->first();

        $subscription['phone_number'] = $subscription->phone_number_formatted;

        $dataRow = [
            'customer'     => $customer,
            'order'        => $order,
            'invoice'      => $order->invoice,
            'subscription' => $subscription,
        ];

        $emailTemplates = EmailTemplate::where('company_id', $customer->company_id)
        ->where('code', 'subscription-change')
        ->get();

        $note = 'Invoice Link- '.route('api.invoice.download', $customer->company_id).'?order_hash='.$order->hash;

        if($subscription->upgrade_downgrade_status == "for-upgrade"){
            $subscriptionsChanged = '<p>'.$subscription->phone_number.' Upgraded from  <b>'.$subscription->oldPlan->name.'</b> to <b>'.$subscription->plans->name.'</b> plan</p>';
        }elseif (isset($subscription->newPlanDetail->name)){
            $subscriptionsChanged = '<p>'.$subscription->phone_number.' Downgrade from  <b>'.$subscription->plans->name.'</b> to <b>'.$subscription->newPlanDetail->name.'</b>  plan</p>';
        } else {
            $subscriptionsChanged = '<p> Addon changes processed </p>';
        }


        foreach ($emailTemplates as $key => $emailTemplate) {
            $row = $this->makeEmailLayout($emailTemplate, $customer, $dataRow);

            $row['body'] = $this->addFieldsToBody('[subscriptions_changed]', $subscriptionsChanged, $row['body']);
	        $configurationSet = $this->setMailConfiguration($customer);

	        if ($configurationSet) {
		        return false;
	        }
            
            Notification::route('mail', $row['email'])->notify(new EmailWithAttachment($order, $pdf, $emailTemplate, $customer->business_verification_id, $row['body'], $row['email'], $note));
        }
    }
}
