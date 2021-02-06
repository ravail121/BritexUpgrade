<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

/**
 * Class SendEmailForNullSubscriptionStartDate
 *
 * @package App\Notifications
 */
class SendEmailForNullSubscriptionStartDate extends Notification
{
	use Queueable;

	/**
	 * @var
	 */
	protected $customers;

	/**
	 * SendEmailForNullSubscriptionStartDate constructor.
	 *
	 * @param $customers
	 */
	public function __construct($customers)
	{
		$this->customers = $customers;
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
			->markdown('mail.email-for-null-subscription-date', ['customers' => $this->customers]);
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
