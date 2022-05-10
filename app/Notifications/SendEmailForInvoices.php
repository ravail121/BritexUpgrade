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
class SendEmailForInvoices extends Notification
{
	use Queueable;

	/**
	 * @var
	 */
	protected $customers;

	/**
	 * @var
	 */
	protected $companyId;

	/**
	 * SendEmailForNullSubscriptionStartDate constructor.
	 *
	 * @param $customers
	 * @param $companyId
	 */
	public function __construct($invoices, $companyId)
	{
		$this->invoices = $invoices;
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
			->markdown('mail.check-invoice', ['invoices' => $this->invoices]);
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
