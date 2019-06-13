<?php

namespace App\Notifications;

use App\Model\Order;
use App\Model\EmailLog;
use App\Model\EmailTemplate;
use Illuminate\Bus\Queueable;
use App\Model\BusinessVerification;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class GenerateMonthlyInvoice extends Notification
{
    use Queueable;

    public $order;
    public $pdf;

    /**
     * Create a new notification instance.
     *
     * @return Order $order
     */
    public function __construct(Order $order, $pdf)
    {
        $this->order = $order;
        $this->pdf   = $pdf;
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

        $emailTemplate = EmailTemplate::where('company_id', $this->order->company_id)->where('code', 'monthly-invoice')->first();
        
        $bizVerification = BusinessVerification::find($this->order->customer->business_verification_id);

        $strings     = ['[FIRST_NAME]', '[LAST_NAME]'];
        
        $replaceWith = [$bizVerification->fname, $bizVerification->lname];


        $body = str_replace($strings, $replaceWith, $emailTemplate->body);

        $data = ['company_id' => $company->id,
            'to'                       => $bizVerification->email,
            'business_verficiation_id' => $bizVerification->id,
            'subject'                  => $emailTemplate->subject,
            'from'                     => $emailTemplate->from,
            'body'                     => $body,
        ];

        $response = $this->emailLog($data);

        return (new MailMessage)
                    ->subject($emailTemplate->subject)
                    ->from($emailTemplate->from)
                    ->line($body)
                    ->attachData($this->pdf->output(), 'monthly-invoice.pdf', [
                        'mime' => 'application/pdf',
                    ]);
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
