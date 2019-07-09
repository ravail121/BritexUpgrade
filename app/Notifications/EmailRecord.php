<?php

namespace App\Notifications;

use App\Model\EmailLog;
use App\Model\EmailTemplate;
use App\Model\SystemEmailTemplateDynamicField;
use Illuminate\Notifications\Messages\MailMessage;

trait EmailRecord 
{
    public function getMailDetails($emailTemplate, $company, $bizVerification, $templateVales)
    {

        $body = $this->createEmailLog($company, $bizVerification, $emailTemplate);

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

    protected function createEmailLog($company, $bizVerification, $emailTemplate, $templateVales)
    {
        $column = array_column($templateVales, 'format_name');
        $body = $emailTemplate->body($column, $bizVerification);

        $data = ['company_id' => $company->id,
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

    public function getEmailWithAttachment($emailTemplate, $company, $bizVerification, $templateVales, $pdf, $type, $attachData)
    {
        $mailMessage = $this->getMailDetails($emailTemplate, $company, $bizVerification, $templateVales);

        $mailMessage->attachData($pdf, $type, $attachData);

        return $mailMessage;
    }
}
