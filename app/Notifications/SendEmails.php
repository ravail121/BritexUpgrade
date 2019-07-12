<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class SendEmails extends Notification
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
