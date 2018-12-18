<?php

namespace App\Notifications;

use App\Model\Order;
use App\Model\EmailTemplate;
use Illuminate\Bus\Queueable;
use App\Model\BusinessVerification;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class BizVerificationApproved extends Notification
{
    use Queueable;

    public $orderHash;
    public $bizVerification;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($orderHash, BusinessVerification $bizVerification)
    {
        $this->orderHash       = $orderHash;
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
        $url = url($this->bizVerification->checkoutUrl().$this->bizVerification->hash.'&order_hash='.$this->orderHash);

        $order         = Order::where('hash', $this->orderHash)->first();
        $emailTemplate = EmailTemplate::where('company_id', $order->company_id)->first();

        $strings     = ['[FIRST_NAME]', '[LAST_NAME]', '[BUSINESS_NAME]', '[HERE]'];
        $replaceWith = [$this->bizVerification->fname, $this->bizVerification->lname, $this->bizVerification->business_name, $url];

        $body = str_replace($strings,$replaceWith, $emailTemplate->body);

        return (new MailMessage)
                    ->subject($emailTemplate->subject)
                    ->from($emailTemplate->from)
                    ->line($body);
                    // ->markdown('mail.biz-verification-approved', [
                    //     'url'           => $url,
                    //     'first_name'    => $this->bizVerification->fname,
                    //     'last_name'     => $this->bizVerification->lname,
                    //     'business_name' => $this->bizVerification->business_name,
                    // ]);
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
