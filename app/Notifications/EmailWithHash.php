<?php

namespace App\Notifications;

use App\Model\Company;
use App\Model\Customer;
use App\Model\EmailLog;
use App\Model\EmailTemplate;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use App\Model\SystemEmailTemplateDynamicField;
use Illuminate\Notifications\Messages\MailMessage;

class EmailWithHash extends Notification
{
    use Queueable;

    public $user;
    
    const URL = '/reset-password?token=';


	/**
	 * EmailWithHash constructor.
	 *
	 * @param $user
	 */
    public function __construct($user)
    {
        $this->user = $user;
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
        $company = Company::find($this->user['company_id']);

        $url = url($company->url.self::URL.$this->user['token']);
        
        $emailTemplate = EmailTemplate::where('company_id', $this->user['company_id'])->where('code', 'reset-password')->first();

        $templateVales  = SystemEmailTemplateDynamicField::where('code', 'reset-password')->get()->toArray();

        $column = array_column($templateVales, 'format_name');
    
        $customer = Customer::whereEmail($this->user['email'])->first();

        $body = $emailTemplate->customerBody($column, $customer);

        $data = [
        	'company_id'                => $customer['company_id'],
            'customer_id'               => $customer['id'],
            'to'                        => $customer['email'],
            'business_verficiation_id'  => $customer['business_verification_id'],
            'subject'                   => $emailTemplate->subject,
            'from'                      => $emailTemplate->from,
            'body'                      => $body
        ];

        $response = $this->emailLog($data);

        return (new MailMessage)
                    ->subject($emailTemplate->subject)
                    ->from($emailTemplate->from)
                    ->line($body)
                    ->action('Reset Password', $url);
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
