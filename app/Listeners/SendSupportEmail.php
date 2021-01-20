<?php

namespace App\Listeners;

use Exception;
use App\Model\EmailTemplate;
use App\Events\SupportEmail;
use Illuminate\Notifications\Notifiable;
use App\Notifications\SendEmailToSupport;
use Illuminate\Notifications\Notification;
use App\Support\Configuration\MailConfiguration;

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
        $company    = \Request::get('company');
        $emailTemplate = EmailTemplate::where('company_id', $company->id)->where('code', 'support-email')->get();
        $configurationSet = $this->setMailConfiguration($company);
        if ($configurationSet) {
            return false;
        }
        try {
            foreach ($emailTemplate as $template) {
                Notification::route('mail', $template->to)->notify(new SendEmailToSupport($data, $template->from));
            }
        } catch (Exception $e) {
            \Log::info($e->getMessage());
            return false;
        }
    }
}
