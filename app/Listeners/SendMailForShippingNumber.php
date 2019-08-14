<?php

namespace App\Listeners;

use Notification;
use App\Model\Order;
use App\Model\Customer;
use App\Model\EmailTemplate;
use App\Listeners\EmailLayout;
use App\Events\AutoPayReminder;
use App\Notifications\SendEmails;
use Illuminate\Notifications\Notifiable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Support\Configuration\MailConfiguration;

class SendMailForShippingNumber
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
        $trackingNumber = $event->trackingNumber;
        $table = $event->table;

        $customer = Customer::find($table->customer_id);

        $configurationSet = $this->setMailConfiguration($customer);

        if ($configurationSet) {
            return false;
        }

        $dataRow = [
            'customer' => $customer,
            'device'   => $table->device,
            'plan'     => $table->plan,
            'sim'      => $table->sim,
        ];

        $emailTemplates = EmailTemplate::where('company_id', $customer->company_id)
        ->where('code','shipping-tracking')
        ->get();

        $order = Order::where('customer_id', $customer->id)->first();

        foreach ($emailTemplates as $key => $emailTemplate) {
            $row = $this->makeEmailLayout($emailTemplate, $customer, $dataRow);

            $row['body'] = $this->addFieldsToBody('[tracking_num]', $trackingNumber, $row['body']);

            Notification::route('mail', $row['email'])->notify(new SendEmails($order, $emailTemplate, $customer->business_verification_id, $row['body'], $row['email']));
        }
    }
}
