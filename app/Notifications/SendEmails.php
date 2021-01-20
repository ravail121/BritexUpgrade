<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Class SendEmails
 *
 * @package App\Notifications
 */
class SendEmails extends Notification
{
    use Queueable, EmailRecord;

	/**
	 * @var
	 */
	public $order;

	/**
	 * @var
	 */
	public $customerTemplate;

	/**
	 * @var
	 */
	public $bizVerification;

	/**
	 * @var
	 */
	public $templateVales;

	/**
	 * @var
	 */
	public $email;

	/**
	 * @var mixed|null
	 */
	public $note;

    /**
     * Create a new notification instance.
     *
     * @return Order $order
     */
    public function __construct($order, $emailTemplate, $bizVerification, $body, $email, $note =null)
    {
        $this->order = $order;
        $this->emailTemplate = $emailTemplate;
        $this->bizVerification = $bizVerification;
        $this->body = $body;
        $this->email = $email;
        $this->note = $note;
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
        $mailMessage = $this->getMailDetails($this->emailTemplate, $this->order, $this->bizVerification, $this->body, $this->email, $this->note);

        return $mailMessage;
    }

}
