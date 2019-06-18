<?php

namespace App\Notifications;

use App\Model\Order;
use App\Model\Company;
use App\Model\EmailLog;
use App\Model\EmailTemplate;
use Illuminate\Bus\Queueable;
use App\Model\BusinessVerification;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Model\SystemEmailTemplateDynamicField;
use Illuminate\Notifications\Messages\MailMessage;

class BizVerification extends Notification
{
    use Queueable;
    
    public $order;
    public $bizVerification;


    /**
     * Create a new notification instance.
     *
     * @return void
     */

    public function __construct(Order $order, BusinessVerification $bizVerification)
    {
        $this->order           = $order;
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
        $company = Company::find($this->order->company_id);

        $emailTemplate = EmailTemplate::where('company_id', $this->order->company_id)->where('code', 'biz-verification-submitted')->first();

        $templateVales  = SystemEmailTemplateDynamicField::where('code', 'biz-verification-submitted')->get()->toArray();

        $column = array_column($templateVales, 'format_name');

        $body = $emailTemplate->body($column, $this->bizVerification);
            
        $mailMessage = (new MailMessage)
                    ->subject($emailTemplate->subject)
                    ->from($emailTemplate->from);
        
        if($emailTemplate->reply_to){
            $mailMessage->replyTo($emailTemplate->reply_to);
        }

        if($emailTemplate->cc){
            $mailMessage->cc($emailTemplate->cc);
        }

        if($emailTemplate->bcc){
            $mailMessage->bcc($emailTemplate->bcc);
        }

        $mailMessage->line($body);

        /*$mailMessage->markdown('vendor.notifications.email', ['company' => $company]);*/

        $data = ['company_id' => $company->id,
            'to'                       => $this->bizVerification->email,
            'business_verficiation_id' => $this->bizVerification->id,
            'subject'                  => $emailTemplate->subject,
            'from'                     => $emailTemplate->from,
            'cc'                       => $emailTemplate->cc,
            'bcc'                      => $emailTemplate->bcc,
            'body'                     => $body,
        ];

        $response = $this->emailLog($data);

        return $mailMessage;
    }

    public function emailLog($data)
    {
        $emailLog = EmailLog::create($data);  

        return $emailLog;
    }
}
