<?php

namespace App\Listeners;

use Notification;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Events\SupportEmail;
use App\Notifications\SendEmailToSupport;
use Illuminate\Notifications\Notifiable;
use App\Support\Configuration\MailConfiguration;
use App\Model\Customer;
use Illuminate\Support\Facades\Request;

class SendSupportEmail
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
     * @param  SupportEmail  $event
     * @return void
     */
    public function handle(SupportEmail $event)
    {
        $data       = $event->data;
        $customer   = Customer::where('email', $data['email'])->first();
        $company    = \Request::get('company');
        
        $configurationSet = $this->setMailConfiguration($company);
        if ($configurationSet) {
            return false;
        }
        Notification::route('mail', $company->support_email)->notify(new SendEmailToSupport($data));
    }
}
