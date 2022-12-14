<?php

namespace App\Http\Controllers\Api\V1\CronJobs;

use Carbon\Carbon;
use App\Model\CronLog;
use Illuminate\Http\Request;
use App\Model\CustomerCreditCard;
use App\Http\Controllers\Controller;
use App\Events\CreditCardExpirationReminder;
use App\Http\Controllers\Api\V1\Traits\CronLogTrait;

/**
 * Class CreditCardExpirationController
 *
 * @package App\Http\Controllers\Api\V1\CronJobs
 */
class CreditCardExpirationController extends Controller
{
	use CronLogTrait;
	/**
	 * @param Request $request
	 */
	public function cardExpirationReminder(Request $request)
	{
		$twoMonthsPriorDate = Carbon::today()->addMonth(2)->format('ny');
		$oneMonthPriorDate = Carbon::today()->addMonth()->format('ny');

		$customerCreditCards = CustomerCreditCard::where('default', 1)
											->where(function($query) use ($twoMonthsPriorDate, $oneMonthPriorDate){
												$query->where( 'expiration', $twoMonthsPriorDate )
		                                        ->orWhere( 'expiration', $oneMonthPriorDate );
											})->with('customer')->get();

		foreach ($customerCreditCards as $customerCreditCard) {
			$request->headers->set('authorization', $customerCreditCard->api_key);
			event(new CreditCardExpirationReminder($customerCreditCard));
			$logEntry = [
				'name'      => CronLog::TYPES['card-expiration-reminder'],
				'status'    => 'success',
				'payload'   => json_encode($customerCreditCard),
				'response'  => 'Reminded successfully for customer ' . $customerCreditCard->customer->id
			];

			$this->logCronEntries($logEntry);
		}
	}
}