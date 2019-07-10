<?php

namespace App\Notifications;

use App\Model\Order;
use App\Model\Company;
use App\Model\EmailLog;
use App\Model\EmailTemplate;
use Illuminate\Bus\Queueable;
use App\Model\BusinessVerification;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Model\SystemEmailTemplateDynamicField;
use Illuminate\Notifications\Messages\MailMessage;

class BizVerificationApproved extends Notification
{
    use Queueable, EmailRecord;
    
    const URL = '/checkout?verification_hash=';

    public $order;
    public $bizVerification;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(Order $order, BusinessVerification $bizVerification)
    {
        $this->order           = $order;
        $this->bizVerification = $bizVerification;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        $company = Company::find($this->order->company_id);

        $url = url($company->url.self::URL.$this->bizVerification->hash.'&order_hash='.$this->order->hash);

        $emailTemplate = EmailTemplate::where('company_id', $this->order->company_id)->where('code', 'biz-verification-approved')->first();

        $templateVales  = SystemEmailTemplateDynamicField::where('code', 'biz-verification-approved')->get()->toArray();

        $mailMessage = $this->getMailDetails($emailTemplate, $this->order, $this->bizVerification, $templateVales);

        return $mailMessage;
    }

}
