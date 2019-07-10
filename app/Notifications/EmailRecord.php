<?php

namespace App\Notifications;

use App\Model\EmailLog;
use App\Model\EmailTemplate;
use App\Model\SystemEmailTemplateDynamicField;
use Illuminate\Notifications\Messages\MailMessage;

trait EmailRecord 
{
    public function getMailDetails($emailTemplate, $order, $bizVerification, $templateVales, $note = null)
    {

        $body = $this->createEmailLog($order, $bizVerification, $emailTemplate, $templateVales, $note);

        $mailMessage = (new MailMessage)
                    ->subject($emailTemplate->subject)
                    ->from($emailTemplate->from);

        if($emailTemplate->reply_to){
            $mailMessage->replyTo($emailTemplate->reply_to);
        }

        if($emailTemplate->cc){
            $cc = explode(",",$emailTemplate->cc);
            $mailMessage->cc($cc);
        }

        if($emailTemplate->bcc){
            $bcc = explode(",",$emailTemplate->bcc);
            $mailMessage->bcc($bcc);
        }

        $mailMessage->line($body);


        return $mailMessage;
    }

    protected function createEmailLog($order, $bizVerification, $emailTemplate, $templateVales, $note)
    {
        $column = array_column($templateVales, 'format_name');
        $body = $emailTemplate->body($column, $bizVerification);

        $data = [
            'company_id'               => $order->company_id,
            'customer_id'              => $order->customer_id,
            'to'                       => $bizVerification->email,
            'business_verficiation_id' => $bizVerification->id,
            'subject'                  => $emailTemplate->subject,
            'from'                     => $emailTemplate->from,
            'cc'                       => $emailTemplate->cc,
            'bcc'                      => $emailTemplate->bcc,
            'body'                     => $body,
            'notes'                    => $note,
        ];

        $emailLog = EmailLog::create($data);

        return $body;
    }

    public function getEmailWithAttachment($emailTemplate, $order, $bizVerification, $templateVales, $pdf, $type, $attachData, $note = null)
    {
        $mailMessage = $this->getMailDetails($emailTemplate, $order, $bizVerification, $templateVales, $note);

        $mailMessage->attachData($pdf, $type, $attachData);

        return $mailMessage;
    }
}
