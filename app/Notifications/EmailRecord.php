<?php

namespace App\Notifications;

use App\Model\EmailLog;
use App\Model\EmailTemplate;
use App\Model\SystemEmailTemplateDynamicField;
use Illuminate\Notifications\Messages\MailMessage;

trait EmailRecord 
{
    public function getMailDetails($emailTemplate, $companyId, $bizVerification, $templateVales)
    {

        $body = $this->createEmailLog($companyId, $bizVerification, $emailTemplate, $templateVales);

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


        return $mailMessage;
    }

    protected function createEmailLog($companyId, $bizVerification, $emailTemplate, $templateVales)
    {
        $column = array_column($templateVales, 'format_name');
        $body = $emailTemplate->body($column, $bizVerification);

        $data = ['company_id' => $companyId,
            'to'                       => $bizVerification->email,
            'business_verficiation_id' => $bizVerification->id,
            'subject'                  => $emailTemplate->subject,
            'from'                     => $emailTemplate->from,
            'cc'                       => $emailTemplate->cc,
            'bcc'                      => $emailTemplate->bcc,
            'body'                     => $body,
        ];

        $response = $this->emailLog($data);
        return $body;
    }

    protected function emailLog($data)
    {
        $emailLog = EmailLog::create($data);  

        return $emailLog;
    }

    public function getEmailWithAttachment($emailTemplate, $companyId, $bizVerification, $templateVales, $pdf, $type, $attachData)
    {
        $mailMessage = $this->getMailDetails($emailTemplate, $companyId, $bizVerification, $templateVales);

        $mailMessage->attachData($pdf, $type, $attachData);

        return $mailMessage;
    }
}
