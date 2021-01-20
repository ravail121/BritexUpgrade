<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class EmailWithAttachment extends Notification
{
    use Queueable  , EmailRecord;

    public $order;
    public $pdf;
    public $emailTemplate;
    public $bizVerificationId;
    public $email;
    public $note;

    /**
     * Create a new notification instance.
     *
     * @return Order $order
     */
    public function __construct($order, $pdf, $emailTemplate, $bizVerificationId, $body, $email, $note)
    {
        $this->order = $order;
        $this->pdf   = $pdf;
        $this->emailTemplate = $emailTemplate;
        $this->bizVerificationId   = $bizVerificationId;
        $this->body = $body;
        $this->email = $email;
        $this->note = $note;
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
        $mailMessage = $this->getEmailWithAttachment($this->emailTemplate, $this->order, $this->bizVerificationId, $this->body, $this->email, $this->pdf->output(), 'Invoice.pdf', ['mime' => 'application/pdf',], $this->note);

        return $mailMessage;
    }

}
