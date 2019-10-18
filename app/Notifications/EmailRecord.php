<?php

namespace App\Notifications;

use App\Model\EmailLog;
use App\Model\EmailTemplate;
use App\Model\SystemEmailTemplateDynamicField;
use Illuminate\Notifications\Messages\MailMessage;

trait EmailRecord 
{
    public function getMailDetails($emailTemplate, $order, $bizVerificationId, $body, $email, $note)
    {
        $this->createEmailLog($order, $bizVerificationId, $emailTemplate, $body , $email, $note);
        $mailMessage = $this->addEmailDetails($emailTemplate, $body);

        return $mailMessage;
    }

    protected function addEmailDetails($emailTemplate, $body)
    {
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

    protected function createEmailLog($order, $bizVerificationId, $emailTemplate, $body, $email, $note)
    {
        $data = [
            'company_id'               => $order->company_id,
            'customer_id'              => $order->customer_id,
            'to'                       => $email,
            'business_verficiation_id' => $bizVerificationId,
            'subject'                  => $emailTemplate->subject,
            'from'                     => $emailTemplate->from,
            'cc'                       => $emailTemplate->cc,
            'bcc'                      => $emailTemplate->bcc,
            'body'                     => $body,
            'notes'                    => $note,
        ];

        $emailLog = EmailLog::create($data);
    }

    public function getEmailWithAttachment($emailTemplate, $order, $bizVerificationId, $body, $email, $pdf, $type, $attachData, $note = null)
    {
        $mailMessage = $this->getMailDetails($emailTemplate, $order, $bizVerificationId, $body, $email, $note);

        $mailMessage->attachData($pdf, $type, $attachData);

        return $mailMessage;
    }
}
