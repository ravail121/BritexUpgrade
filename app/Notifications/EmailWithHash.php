<?php

namespace App\Notifications;

use App\Model\Company;
use App\Model\EmailTemplate;
use Illuminate\Bus\Queueable;
use App\Model\BusinessVerification;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class EmailWithHash extends Notification
{
    use Queueable;
    const URL = '/reset-password?token=';


    /**
     * Create a new notification instance.
     *
     * @return Order $order
     */
    public function __construct($user)
    {
        $this->user = $user;
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
        \Log::info("notify");
        \Log::info($this->user);
        $company = Company::find($this->user['company_id']);

        $url = url($company->url.self::URL.$this->user['token']);

        \Log::info($url);

        return (new MailMessage)
                    ->subject("Reset Password")
                    ->from("admin@teltik.pw")
                    ->line('Please reset you password by clicking on the link')
                    ->action('Verify', $url);
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
