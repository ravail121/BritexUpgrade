<?php

namespace App\Http\Controllers\Api\V1\CronJobs;

use App\Helpers\Log;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Model\CustomerCreditCard;
use App\Http\Controllers\Controller;
use App\Events\CreditCardExpirationReminder;

/**
 * Class CreditCardExpirationController
 *
 * @package App\Http\Controllers\Api\V1\CronJobs
 */
class CreditCardExpirationController extends Controller
{
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
											})->with('customer')->first();

		foreach ($customerCreditCards as $customerCreditCard) {
			Log::info($customerCreditCard->customer_id, 'Customer Id Card Expiration');
			$request->headers->set('authorization', $customerCreditCard->api_key);
			event(new CreditCardExpirationReminder($customerCreditCard));
		}
	}
}