<?php

namespace App\Notifications\AccountSuspended;

use App\Model\Order;
use App\Model\EmailLog;
use App\Model\EmailTemplate;
use Illuminate\Bus\Queueable;
use App\Notifications\EmailRecord;
use App\Model\BusinessVerification;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Model\SystemEmailTemplateDynamicField;
use Illuminate\Notifications\Messages\MailMessage;

class InformCustomerAccountSuspended extends Notification
{
    use Queueable, EmailRecord;

    public $order;
    public $customerTemplate;
    public $bizVerification;
    public $templateVales;

    /**
     * Create a new notification instance.
     *
     * @return Order $order
     */
      
    public function __construct(Order $order, $customerTemplate, $bizVerification, $templateVales)
    {
        $this->order = $order;
        $this->customerTemplate = $customerTemplate;
        $this->bizVerification = $bizVerification;
        $this->templateVales = $templateVales;
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
        $mailMessage = $this->getMailDetails($this->customerTemplate, $this->order, $this->bizVerification, $this->templateVales);

        return $mailMessage;
    }

}
