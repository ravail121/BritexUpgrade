<?php

namespace App\Listeners;

use Notification;
use App\Model\Order;
use App\Model\EmailTemplate;
use App\Notifications\SendEmails;
use Illuminate\Notifications\Notifiable;
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
        $customer = $table->customer;

        $dataRow = [
            'customer'    => $table->customer,
        ];

        if($table->device && $table->sim){
            $productName = $table->device->name.' & '.$table->sim->name;
        }elseif ($table->device) {
            $productName = $table->device->name;
        }elseif ($table->sim) {
            $productName = $table->sim->name;
        }

        $emailTemplates = EmailTemplate::where('company_id', $dataRow['customer']['company_id'])
        ->where('code', 'shipping-tracking')
        ->get();

        $order = Order::where('customer_id', $customer->id)->first();

        foreach ($emailTemplates as $key => $emailTemplate) {
            $row = $this->makeEmailLayout($emailTemplate, $customer, $dataRow);

            $row['body'] = $this->addFieldsToBody(['[product_name]','[tracking_num]'], [$productName, $trackingNumber], $row['body']);

	        $configurationSet = $this->setMailConfiguration($table->customer);

	        if ($configurationSet) {
		        return false;
	        }

            Notification::route('mail', $row['email'])->notify(new SendEmails($order, $emailTemplate, $customer->business_verification_id, $row['body'], $row['email']));
        }
    }
}
