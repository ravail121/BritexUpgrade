<?php

namespace App\Notifications;

use App\Model\Company;
use App\Model\EmailTemplate;
use Illuminate\Bus\Queueable;
use App\Model\Customer;
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
        $company = Company::find($this->user['company_id']);

        $url = url($company->url.self::URL.$this->user['token']);

        $emailTemplate = EmailTemplate::where('company_id', $this->user['company_id'])->where('code', 'reset-password')->first();

        $customer = Customer::whereEmail($this->user['email'])->first();

        $strings     = ['[FIRST_NAME]', '[LAST_NAME]','[EMAIL]'];
        
        $replaceWith = [$customer['fname'], $customer['lname'], $customer['email']];

        $body = str_replace($strings, $replaceWith, $emailTemplate->body);

        return (new MailMessage)
                    ->subject($emailTemplate->subject)
                    ->from($emailTemplate->from)
                    ->line($body)
                    ->action('Reset Password', $url);
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
