<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class SendEmailToSupport extends Notification
{
    use Queueable, EmailRecord;
    public $data, $from;
    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($data, $from)
    {
        $this->data = $data;
        $this->from = $from;
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
        
        return (new MailMessage)
                    ->subject($this->data['subject'])
                    ->from($this->from)
                    ->line('<strong>From:</strong>'.' '.$this->data['email'])
                    ->line('<strong>Name:</strong>'.' '.$this->data['name'])
                    ->line('<strong>Message:</strong>'.' '.$this->data['message']);
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            //
        ];
    }
}
