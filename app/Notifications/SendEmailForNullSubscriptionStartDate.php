<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Class SendEmailForNullSubscriptionStartDate
 *
 * @package App\Notifications
 */
class SendEmailForNullSubscriptionStartDate extends Notification
{
	use Queueable, EmailRecord;

	protected $customers;

	/**
	 * Create a new notification instance.
	 *
	 * @return Order $order
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
	 * Build the message.
	 *
	 * @return $this
	 */
	public function build()
	{
		return $this->view('mail.email-for-null-subscription-date');
	}

}
