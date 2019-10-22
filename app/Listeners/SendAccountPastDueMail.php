<?php

namespace App\Listeners;


use Notification;
use App\Model\Order;
use App\Model\EmailTemplate;
use App\Listeners\EmailLayout;
use App\Notifications\SendEmails;
use Illuminate\Notifications\Notifiable;
use App\Support\Configuration\MailConfiguration;

class SendAccountPastDueMail
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
        $customer = $event->customer;
        $subscriptions = $event->subscriptions;
        $amount = $event->amount;

        $configurationSet = $this->setMailConfiguration($customer);

        if ($configurationSet) {
            return false;
        }

        $dataRow['customer'] = $customer;

        $emailTemplates = EmailTemplate::where('company_id', $customer->company_id)
        ->where('code', 'account-pastdue')
        ->get();
        $na = 'NA';
        $order = Order::where('customer_id', $customer->id)->first();
        $subscriptionList = '<table style="width:100%">
                                <tr>
                                    <th>Phone Number</th>
                                    <th>Plan</th>
                                    <th>Addon</th>
                                </tr>';
        foreach ($subscriptions as $key => $subscription) {

            if(isset($subscription->subscriptionAddonNotRemoved['0'])){
                $addons = $subscription->subscriptionAddonNotRemoved->pluck('addons')->pluck('name')->toArray();
                if(isset($addons['0'])){
                    $addon = implode(", ",$addons);
                }
            }else{
                $addon = "NA";
            }

            $subscriptionList .='<tr>
                                    <td>'.$subscription->phone_number_formatted.'</td>
                                    <td>'.$subscription->plan->name.'</td>
                                    <td>'.$addon.'</td>
                                </tr>';
        }
        
        foreach ($emailTemplates as $key => $emailTemplate) {
            $row = $this->makeEmailLayout($emailTemplate, $customer, $dataRow);

            $row['body'] = $this->addFieldsToBody(['[balance_due]','[active_subscriptions_list]'], [$amount, $subscriptionList], $row['body']);

            Notification::route('mail', $row['email'])->notify(new SendEmails($order, $emailTemplate, $customer->business_verification_id, $row['body'], $row['email']));
        }
    }
}
