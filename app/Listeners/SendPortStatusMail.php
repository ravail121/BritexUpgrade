<?php

namespace App\Listeners;

use App\Events\PortPending;
use App\Model\Port;
use App\Model\EmailTemplate;
use App\Notifications\SendEmails;
use Illuminate\Notifications\Notifiable;
use Notification;
use App\Support\Configuration\MailConfiguration;

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
        $port = Port::where('id', $event->portId)->with('subscription.order', 'subscription.customerRelation')->first();
	    if($port->subscription()->exists()) {
		    $subscription   = $port->subscription;
		    $order          = $subscription->order;
		    $customer       = $subscription->customerRelation;
		    $emailTemplates = EmailTemplate::where( 'company_id', $customer->company_id )->where( 'code', 'port-pending' )->get();

		    $dataRow[ 'customer' ]     = $customer;
		    $dataRow[ 'port' ]         = $port;
		    $dataRow[ 'subscription' ] = $subscription;

		    foreach ( $emailTemplates as $key => $emailTemplate ) {
			    $row = $this->makeEmailLayout( $emailTemplate, $customer, $dataRow );

			    $configurationSet = $this->setMailConfiguration( $customer );

			    if ( $configurationSet ) {
				    return false;
			    }

			    Notification::route( 'mail', $row[ 'email' ] )->notify( new SendEmails( $order, $emailTemplate, $customer->business_verification_id, $row[ 'body' ], $row[ 'email' ] ) );
		    }
	    }
    }
}
