<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

/**
 * Class SendEmailForNullSchedulerStatus
 *
 * @package App\Notifications
 */
class EmailForSchedulerStatus extends Notification
{
	use Queueable;

	/**
	 * @var
	 */
	protected $cronEntries;

	/**
	 * @var
	 */
	protected $companyId;

	/**
	 * SendEmailForNullSchedulerStatus constructor.
	 *
	 * @param $cronEntries
	 * @param $companyId
	 */
	public function __construct($cronEntries, $companyId)
	{
		$this->cronEntries = $cronEntries;
		$this->companyId = $companyId;
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
	 * @param $notifiable
	 *
	 * @return MailMessage
	 */
	public function toMail($notifiable)
	{
		return (new MailMessage)
			->subject('Alert')
			->from('postmaster@mg.teltik.com')
			->markdown('mail.email-for-scheduler-status', ['cronEntries' => $this->cronEntries]);
	}

	/**
	 * Get the array representation of the notification.
	 *
	 * @param  mixed  $notifiable
	 * @return array
	 */
	public function toArray($notifiable)
	{
		return [
			//
		];
	}

}
