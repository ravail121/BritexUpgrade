<?php

namespace App\Listeners;

use Notification;
use App\Model\Order;
use App\Model\Subscription;
use App\Model\EmailTemplate;
use App\Model\Company;
use App\Notifications\SendEmails;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Notifications\Notifiable;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Support\Configuration\MailConfiguration;
use Illuminate\Support\Facades\Config;

class SendEmailForChangedSubcriptionStatus
{
    use EmailLayout, Notifiable, MailConfiguration;

    const STATUS_CODE = [
        'for-activation'   => 'for-activation',
        'active'           => 'activation-complete',
        'closed'           => 'closed',
    ];

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
        $subscriptionId = $event->subscriptionId;
        
        $subscription = Subscription::where('id', $subscriptionId)->with('customerRelation', 'plans', 'ban')->first();
        
        $dataRow = [
            'subscription' => $subscription,
            'customer'    => $subscription->customerRelation,
            'plan'        => $subscription->plans,
            'ban'         => $subscription->ban,
        ];
        $addons = $subscription->namesOfSubscriptionAddonNotRemoved;
        $addonsName = $addons->implode(',');

        $configurationSet = $this->setMailConfigurationById($dataRow['customer']['company_id']);

        if ($configurationSet) {
            return false;
        }

        $order = Order::where('customer_id', $dataRow['customer']['id'])->first();

        $emailTemplates = EmailTemplate::where('company_id', $dataRow['customer']['company_id'])
        ->where('code', self::STATUS_CODE[$subscription->status])
        ->get();

        foreach ($emailTemplates as $key => $emailTemplate) {
            $row = $this->makeEmailLayout($emailTemplate, $dataRow['customer'], $dataRow);

            $row['body'] = $this->addFieldsToBody(['[addon__name]'], [$addonsName], $row['body']);

            Notification::route('mail', $row['email'])->notify(new SendEmails($order, $emailTemplate, $dataRow['customer']['business_verification_id'] , $row['body'], $row['email']));
        }

    }

    public function setMailConfigurationById($companyId)
    {
        $company = Company::find($companyId);
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
