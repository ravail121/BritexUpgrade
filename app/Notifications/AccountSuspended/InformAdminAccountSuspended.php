<?php

namespace App\Notifications\AccountSuspended;

use App\Model\Order;
use App\Model\Customer;
use App\Model\EmailLog;
use App\Model\EmailTemplate;
use Illuminate\Bus\Queueable;
use App\Model\BusinessVerification;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class InformAdminAccountSuspended extends Notification
{
    use Queueable;

    public $order;

    /**
     * Create a new notification instance.
     *
     * @return Order $order
     */
    public function __construct(Order $order)
    {
        $this->order = $order;
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
        $adminTemplate = EmailTemplate::where('company_id', $this->order->company_id)->where('code', 'account-suspension-admin')->first();

        $customer = Customer::find($this->order->customer_id);
        
        $bizVerification = BusinessVerification::find($this->order->customer->business_verification_id);

        $strings     = ['[FIRST_NAME]', '[LAST_NAME]'];
        $replaceWith = [$bizVerification->fname, $bizVerification->lname];

        $adminBody   = str_replace($strings, $replaceWith, $adminTemplate->body);

        $data = ['company_id' => $this->order->company_id,
            'customer_id'              => $customer->id,
            'to'                       => $customer->email,
            'business_verficiation_id' => $customer->business_verification_id,
            'subject'                  => $adminTemplate->subject,
            'from'                     => $adminTemplate->from,
            'body'                     => $adminBody,
        ];

        $response = $this->emailLog($data);
        
        return (new MailMessage)
                    ->subject($adminTemplate->subject)
                    ->from($adminTemplate->from)
                    ->line($adminBody);
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function emailLog($data)
    {
        $emailLog = EmailLog::create($data);  

        return $emailLog;
    }

}
