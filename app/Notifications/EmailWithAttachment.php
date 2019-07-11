<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class EmailWithAttachment extends Notification
{
    use Queueable  , EmailRecord;

    public $order;
    public $pdf;
    public $emailTemplate;
    public $bizVerification;
    public $templateValues;
    public $note;

    /**
     * Create a new notification instance.
     *
     * @return Order $order
     */
    public function __construct($order, $pdf, $emailTemplate, $bizVerification, $templateValues, $note)
    {
        $this->order = $order;
        $this->pdf   = $pdf;
        $this->emailTemplate = $emailTemplate;
        $this->bizVerification   = $bizVerification;
        $this->templateValues = $templateValues;
        $this->note   = $note;
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
        $mailMessage = $this->getEmailWithAttachment($this->emailTemplate, $this->order, $this->bizVerification, $this->templateValues, $this->pdf->output(), 'invoice.pdf', ['mime' => 'application/pdf',], $this->note);

        return $mailMessage;
    }

}
