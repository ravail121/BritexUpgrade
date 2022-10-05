<?php

namespace App\Notifications;

use App\Model\Order;
use App\Model\EmailTemplate;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use App\Model\SystemEmailTemplateDynamicField;

class GenerateMonthlyInvoice extends Notification
{
    use Queueable , EmailRecord;

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

        $templateVales  = SystemEmailTemplateDynamicField::where('code', 'one-time-invoice')->get()->toArray();

        $note = 'Invoice Link '.route('api.invoice.get').'?order_hash='.$this->order->hash;

        $mailMessage = $this->getEmailWithAttachment($emailTemplate, $this->order, $this->order->customer->business_verification_id, $templateVales, $this->pdf->output(), 'monthly-invoice.pdf', ['mime' => 'application/pdf',], $note);

        return $mailMessage;
//commented previous code because it was not tested on server
    //     $column = array_column($templateVales, 'format_name');

    //     $body = $emailTemplate->body($column, $bizVerification);

    //     $data = ['company_id' => $this->order->company_id,
    //         'to'                       => $bizVerification->email,
    //         'business_verficiation_id' => $bizVerification->id,
    //         'subject'                  => $emailTemplate->subject,
    //         'from'                     => $emailTemplate->from,
    //         'body'                     => $body,
    //     ];

    //     $response = $this->emailLog($data);

    //     return (new MailMessage)
    //                 ->subject($emailTemplate->subject)
    //                 ->from($emailTemplate->from)
    //                 ->line($body)
    //                 ->attachData($this->pdf->output(), 'monthly-invoice.pdf', [
    //                     'mime' => 'application/pdf',
    //                 ]);
    // }

    // /**
    //  * Get the array representation of the notification.
    //  *
    //  * @param  mixed  $notifiable
    //  * @return array
    //  */
    // public function emailLog($data)
    // {
    //     $emailLog = EmailLog::create($data);  

    //     return $emailLog;
    }

}
