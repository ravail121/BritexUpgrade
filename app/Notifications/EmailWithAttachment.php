<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class EmailWithAttachment extends Notification
{
    use Queueable  , EmailRecord;

	/**
	 * @var
	 */
    public $order;

	/**
	 * @var
	 */
    public $pdf;

	/**
	 * @var
	 */
    public $emailTemplate;

	/**
	 * @var
	 */
    public $bizVerificationId;

	/**
	 * @var
	 */
    public $email;

	/**
	 * @var
	 */
    public $note;

	/**
	 *
	 */
    public $is_csv_enabled;

	/**
	 * Create a new notification instance.
	 * @param       $order
	 * @param       $pdf
	 * @param       $emailTemplate
	 * @param       $bizVerificationId
	 * @param       $body
	 * @param       $email
	 * @param       $note
	 * @param false $is_csv_enabled
	 */
    public function __construct($order, $pdf, $emailTemplate, $bizVerificationId, $body, $email, $note, $is_csv_enabled=false)
    {
        $this->order = $order;
        $this->pdf   = $pdf;
        $this->emailTemplate = $emailTemplate;
        $this->bizVerificationId = $bizVerificationId;
        $this->body = $body;
        $this->email = $email;
        $this->note = $note;
		$this->is_csv_enabled = $is_csv_enabled;
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
		if($this->is_csv_enabled) {
			$mailMessage = $this->getEmailWithAttachment( $this->emailTemplate, $this->order, $this->bizVerificationId, $this->body, $this->email, $this->pdf, 'Invoice.csv', [ 'mime' => 'text/csv', ], $this->note );
		} else {
			$mailMessage = $this->getEmailWithAttachment( $this->emailTemplate, $this->order, $this->bizVerificationId, $this->body, $this->email, $this->pdf->output(), 'Invoice.pdf', [ 'mime' => 'application/pdf', ], $this->note );
		}

        return $mailMessage;
    }

}
