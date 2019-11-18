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
        'suspended'        => 'suspended'
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
        
        $subscription = Subscription::where('id', $subscriptionId)->with('customerRelation', 'plans')->first();
        
        $dataRow = [
            'subscription' => $subscription,
            'customer'    => $subscription->customerRelation,
            'plan'        => $subscription->plans,
        ];
        $addons = $subscription->namesOfSubscriptionAddonNotRemoved;
        $addonsName = $addons->implode(',');

        $configurationSet = $this->setMailConfigurationById($dataRow['customer']['company_id']);
        if ($configurationSet) {
            return false;
        }

        $emailTemplates = EmailTemplate::where('company_id', $dataRow['customer']['company_id'])
        ->where('code', self::STATUS_CODE[$subscription->status])
        ->get();

        foreach ($emailTemplates as $key => $emailTemplate) {
            $row = $this->makeEmailLayout($emailTemplate, $dataRow['customer'], $dataRow);

            $row['body'] = $this->addFieldsToBody(['[addon__name]'], [$addonsName], $row['body']);

            Notification::route('mail', $row['email'])->notify(new SendEmails($dataRow['customer'], $emailTemplate, $dataRow['customer']['business_verification_id'] , $row['body'], $row['email']));
        }
    }
}
