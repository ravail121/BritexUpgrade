<?php


namespace App\Notifications;

use Illuminate\Support\Facades\Log;
use Swift_Mailer;
use App\Model\Company;
use App\Model\Customer;
use Swift_SmtpTransport;
use Illuminate\Notifications\Channels\MailChannel;
use Illuminate\Notifications\Notification;


/**
 * Class DynamicSmtpMailChannel
 *
 * @package App\Notifications
 */
class DynamicSmtpMailChannel extends MailChannel
{
	/**
	 * Send the given notification.
	 *
	 * @param mixed $notifiable
	 * @param \Illuminate\Notifications\Notification $notification
	 * @return void
	 */
	public function send($notifiable, Notification $notification)
	{
		if (isset($notification->customer)) {
			$companyId = $notification->customer->company_id;
		}

		if (!isset($companyId) && isset($notification->customerId)) {
			$customer = Customer::find($notification->customerId);
			$companyId = $customer->company_id;
		}

		if (!isset($companyId) && isset($notification->email)) {

			$customer = Customer::where('email', $notification->email);
			if($customer->exists()) {
				$companyId = $customer->company_id;
			}
		}

		if (!isset($companyId) && isset($notification->data['company_id'])) {
			$companyId = $notification->data['company_id'];
		}

		if(isset($notification->order)){
			$companyId = $notification->order->company_id;
		}

		if (isset($companyId)) {
			$company = Company::find($companyId);
			Log::info('DynamicSmtpMailChannel send');
			Log::info($company->id);
			Log::info($company->smtp_host);

			$customSmtp = [
				'driver'        => $company->smtp_driver,
				'host'          => $company->smtp_host,
				'port'          => $company->smtp_port,
				'username'      => $company->smtp_username,
				'password'      => $company->smtp_password,
				'encryption'    => $company->smtp_encryption,
			];

			$previousSwiftMailer = $this->mailer->getSwiftMailer();

			$swiftTransport = new Swift_SmtpTransport(
				$customSmtp['host'],
				$customSmtp['port'],
				$customSmtp['encryption']
			);

			$swiftTransport->setUsername($customSmtp['username']);
			$swiftTransport->setPassword($customSmtp['password']);

			$this->mailer->setSwiftMailer(new Swift_Mailer($swiftTransport));
		}

		$result = parent::send($notifiable, $notification);

		if (isset($previousSwiftMailer)) {
			//restore the previous mailer
			$this->mailer->setSwiftMailer($previousSwiftMailer);
		}

		return $result;
	}
}