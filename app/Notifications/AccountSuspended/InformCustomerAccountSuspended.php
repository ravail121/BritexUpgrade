<?php

namespace App\Notifications\AccountSuspended;

use App\Model\Order;
use App\Model\EmailLog;
use App\Model\EmailTemplate;
use Illuminate\Bus\Queueable;
use App\Model\BusinessVerification;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Model\SystemEmailTemplateDynamicField;
use Illuminate\Notifications\Messages\MailMessage;

class InformCustomerAccountSuspended extends Notification
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
        $customerTemplate = EmailTemplate::where('company_id', $this->order->company_id)->where('code', 'account-suspension-customer')->first();
        
        $bizVerification = BusinessVerification::find($this->order->customer->business_verification_id);

        $bizVerification = BusinessVerification::find($this->order->customer->business_verification_id);

        $templateVales  = SystemEmailTemplateDynamicField::where('code', 'account-suspension-customer')->get()->toArray();

        $column = array_column($templateVales, 'format_name');

        $customerBody = $emailTemplate->body($column, $bizVerification);

        $data = ['company_id' => $this->order->company_id,
            'to'                       => $bizVerification->email,
            'business_verficiation_id' => $bizVerification->id,
            'subject'                  => $customerTemplate->subject,
            'from'                     => $customerTemplate->from,
            'body'                     => $customerBody,
        ];

        $response = $this->emailLog($data);

        return (new MailMessage)
                    ->subject($customerTemplate->subject)
                    ->from($customerTemplate->from)
                    ->line($customerBody);
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
