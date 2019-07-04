<?php

namespace App\Notifications;

use App\Model\Order;
use App\Model\EmailTemplate;
use Illuminate\Bus\Queueable;
use App\Model\BusinessVerification;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Model\SystemEmailTemplateDynamicField;
use Illuminate\Notifications\Messages\MailMessage;
use App\Model\EmailLog;

class EmailWithAttachment extends Notification
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

        $bizVerification = BusinessVerification::find($this->order->customer->business_verification_id);

        $emailTemplate = '';
        $templateValues = '';

        if ($this->order->invoice->type == 2) {
            $emailTemplate      = EmailTemplate::where('company_id', $this->order->company_id)->where('code', 'one-time-invoice')->first();
            $templateValues     = SystemEmailTemplateDynamicField::where('code', 'one-time-invoice')->get()->toArray();
        
        } elseif ($this->order->invoice->type == 1) {
            $emailTemplate      = EmailTemplate::where('company_id', $this->order->company_id)->where('code', 'monthly-invoice')->first();
            $templateValues     = SystemEmailTemplateDynamicField::where('code', 'monthly-invoice')->get()->toArray();

        }
       
        $strings     = ['[FIRST_NAME]', '[LAST_NAME]'];
       
        $replaceWith = [$bizVerification->fname, $bizVerification->lname];
        
        $column = array_column($templateValues, 'format_name');

        $body = $emailTemplate->body($column, $bizVerification);
        
        $data = ['company_id' => $this->order->company_id,
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
                    ->attachData($this->pdf->output(), 'invoice.pdf', [
                        'mime' => 'application/pdf',
                    ]);
                    // ->attachData($this->pdf, 'invoice.pdf', [
                    //     'mime' => 'application/pdf',
                    // ]);
    }

    public function emailLog($data)
    {
        $emailLog = EmailLog::create($data);  

        return $emailLog;
    }

}
