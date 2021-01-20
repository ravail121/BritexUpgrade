<?php

namespace App\Http\Controllers\Api\V1\CronJobs;

use App\Model\Coupon;
use App\Model\Device;
use App\Model\Plan;
use App\Model\Sim;
use Exception;
use App\Model\Order;
use GuzzleHttp\Client;
use App\Model\Invoice;
use App\Http\Modules\ReadyCloud;
use App\Http\Controllers\BaseController;
use Illuminate\Database\Eloquent\Builder;
use App\Http\Controllers\Api\V1\Invoice\InvoiceController;

/**
 * Class OrderController
 *
 * @package App\Http\Controllers\Api\V1\CronJobs
 */
class OrderController extends BaseController
{

	/**
	 * @param null $orderID
	 *
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function order($orderID = null)
	{
		if($orderID){
			$orders = Order::where('id', $orderID)->get();
		}else{
			$orders = Order::where('status', '1')->with('subscriptions', 'standAloneDevices', 'standAloneSims', 'customer', 'invoice.invoiceItem', 'payLog')->whereHas('subscriptions', function(Builder $subscription) {
				$subscription->where([['status', 'shipping'],['sent_to_readycloud', 0 ]]);
			})->orWhereHas('standAloneDevices', function(Builder $standAloneDevice) {
				$standAloneDevice->where([['status', 'shipping'],['processed', 0 ]]);
			})->orWhereHas('standAloneSims', function(Builder $standAloneSim) {
				$standAloneSim->where([['status', 'shipping'],['processed', 0 ]]);
			})->with('company')->get();
		}


		try {
			foreach ($orders as $orderKey => $order) {
				$readyCloudApiKey = $order->company->readycloud_api_key;
				if($readyCloudApiKey == null){
					continue;
				}
				\Log::info([$order->id, $order->order_num]);

				$subscriptionRow = array();
				$standAloneDeviceRow = array();
				$standAloneSimRow = array();

				foreach ($order->subscriptions as $key => $subscription) {
					$responseData = $this->subscriptions($subscription, $order->invoice->invoiceItem);
					if($responseData){
						$subscriptionRow[$key] = $responseData;
					}
				}

				foreach ($order->standAloneDevices as $key => $standAloneDevice) {
					$standAloneDeviceRow[$key] = $this->standAloneDevice($standAloneDevice, $order->invoice->invoiceItem);
				}

				foreach ($order->standAloneSims as $key => $standAloneSim) {
					$standAloneSimRow[$key] = $this->standAloneSim($standAloneSim, $order->invoice->invoiceItem);
				}
				$row[0]['items'] = array_merge($subscriptionRow, $standAloneDeviceRow, $standAloneSimRow);
				$apiData = $this->data($order, $row);
				\Log::info('Ready Cloud');
				\Log::info($apiData);

				$response = $this->SentToReadyCloud($apiData, $readyCloudApiKey);
				if($response){
					if($response->getStatusCode() == 201) {
						$order->subscriptions()->update(['sent_to_readycloud' => 1]);
						$order->standAloneDevices()->update(['processed' => 1]);
						$order->standAloneSims()->update(['processed' => 1]);
					} else {
						return $this->respond(['message' => 'Something went wrong!']);
					}
				}

			}
		}catch (Exception $e) {
			\Log::error($e->getMessage());
		}

		return $this->respond(['message' => 'Orders Shipped Successfully.']);
	}

	/**
	 * @param $subscription
	 * @param $invoiceItem
	 *
	 * @return string[]
	 */
	public function subscriptions($subscription, $invoiceItem)
	{
		if(($subscription->sim_id != 0) && ($subscription->device_id == 0)) {
			return  $this->subscriptionWithSim($subscription, $invoiceItem);
		}

		if(($subscription->device_id != 0) && ($subscription->sim_id == 0)) {
			return  $this->subscriptionWithDevice($subscription, $invoiceItem);
		}

		if(($subscription->sim_id != 0) && ($subscription->device_id != 0)) {
			return $this->subscriptionWithSimAndDevice($subscription, $invoiceItem);
			// $simData = $this->subscriptionWithSim($subscription);
			// $deviceData = $this->subscriptionWithDevice($subscription);
			// return [$simData, $deviceData];
		}
	}

	/**
	 * @param $subscription
	 * @param $invoiceItem
	 *
	 * @return string[]
	 */
	public function subscriptionWithSim($subscription, $invoiceItem)
	{
		$simAmount = $invoiceItem->where( 'subscription_id', $subscription->id )
		                         ->where( 'product_type', InvoiceController::SIM_TYPE )
		                         ->where( 'product_id', $subscription->sim_id )
		                         ->sum( 'amount' );

		$planAmount = 0;

		if (isset($subscription->plan_id) && $subscription->plan_id != null) {
			$planAmount = $invoiceItem->where( 'subscription_id', $subscription->id )
			                          ->where( 'product_type', InvoiceController::PLAN_TYPE )
			                          ->where( 'product_id', $subscription->plan_id )
			                          ->sum( 'amount' );
		}
		$amount = $simAmount + $planAmount;
		return [
			'description'   => $subscription->sim_name . ' ($'. $subscription->sim['amount_w_plan'] . ') associated with '. $subscription->plan['name'] . ' ($' . $planAmount . ')',
			'part_number'   => 'SUB-'.$subscription->id,
			'unit_price'    => $amount.' USD',
			'quantity'      => '1',
		];
	}

	/**
	 * @param $subscription
	 * @param $invoiceItem
	 *
	 * @return string[]
	 */
	public function subscriptionWithDevice($subscription, $invoiceItem)
	{
		$deviceAmount = $invoiceItem->where(
			'subscription_id', $subscription->id)
		                            ->where(
			                            'product_type', InvoiceController::DEVICE_TYPE
		                            )->where(
				'product_id',  $subscription->device_id
			)->sum('amount');

		$planAmount = 0;

		if (isset($subscription->plan_id) && $subscription->plan_id != null) {
			$planAmount = $invoiceItem->where( 'subscription_id', $subscription->id )
			                          ->where( 'product_type', InvoiceController::PLAN_TYPE )
			                          ->where( 'product_id', $subscription->plan_id )
			                          ->sum( 'amount' );
		}

		$amount = $deviceAmount + $planAmount;

		return [
			'description'   => $subscription->device['name'] . ' ($'. $subscription->device['amount_w_plan'] .') associated with ' . $subscription->plan['name'] .' ($' . $planAmount .')',
			'part_number'   => 'SUB-' . $subscription->id,
			'unit_price'    => $amount .' USD',
			'quantity'      => '1'
		];
	}

	/**
	 * @param $subscription
	 * @param $invoiceItem
	 *
	 * @return string[]
	 */
	public function subscriptionWithSimAndDevice($subscription, $invoiceItem)
	{
		$simAndDeviceAmount = $invoiceItem->where('subscription_id', $subscription->id)->whereIn('product_type', [InvoiceController::DEVICE_TYPE, InvoiceController::SIM_TYPE])->sum('amount');

		$planAmount = 0;
		if (isset($subscription->plan_id) && $subscription->plan_id != null) {
			$planAmount = $invoiceItem->where( 'subscription_id', $subscription->id )
			                          ->where( 'product_type', InvoiceController::PLAN_TYPE )
			                          ->where( 'product_id', $subscription->plan_id )
			                          ->sum( 'amount' );
		}

		$amount = $simAndDeviceAmount + $planAmount;

		return [
			'description'   => $subscription->sim_name . ' ($'. $subscription->sim['amount_w_plan'] . ') associated with ' . $subscription->plan['name'] . ' ($' . $planAmount . ') and ' . $subscription->device['name'] . ' ($' . $subscription->device['amount_w_plan'] . ')',
			'part_number'   => 'SUB-'.$subscription->id,
			'unit_price'    => $amount.' USD',
			'quantity'      => '1',
		];
	}

	/**
	 * @param $standAloneDevice
	 * @param $invoiceItem
	 *
	 * @return array
	 */
	public function standAloneDevice($standAloneDevice, $invoiceItem)
	{
		$device           = Device::find($standAloneDevice->device_id);
		$invoiceItemAmount = $invoiceItem->where(
			'product_type', InvoiceController::DEVICE_TYPE
		)->where(
			'product_id',  $device->id
		)->where(
			'subscription_id', 0
		);

		$amount = 0;
		foreach ($invoiceItemAmount->toArray() as $key => $value) {
			$amount = $value['amount'];
			break;
		}
		\Log::info('Ready Cloud StandAlone Device: ');
		\Log::info($amount);
		return [
			'description'   => $standAloneDevice->device['name'] . ' ($' . $amount . ')',
			'part_number'   => 'DEV-'.$standAloneDevice->id,
			'unit_price'    => $amount.' USD',
			'quantity'      => '1',
		];
	}

	/**
	 * @param $standAloneSim
	 * @param $invoiceItem
	 *
	 * @return array
	 */
	public function standAloneSim($standAloneSim, $invoiceItem)
	{
		$sim           = Sim::find($standAloneSim->sim_id);
		$invoiceItemAmount = $invoiceItem->where(
			'product_type', InvoiceController::SIM_TYPE
		)->where(
			'product_id', $sim->id
		)->where(
			'subscription_id', 0
		);

		$amount = 0;
		foreach ($invoiceItemAmount->toArray() as $key => $value) {
			$amount = $value['amount'];
			break;
		}
		\Log::info('Ready Cloud StandAlone SIM: ');
		\Log::info($amount);
		return [
			'description'   => $standAloneSim->sim['name']. ' ($' . $amount . ')',
			'part_number'   => 'SIM-'.$standAloneSim->id,
			'unit_price'    => $amount.' USD',
			'quantity'      => '1'
		];
	}

	/**
	 * @param $order
	 * @param $row
	 *
	 * @return false|string
	 */
	public function data($order, $row)
	{
		$taxes = $order->invoice->invoiceItem->whereIn('type', [Invoice::InvoiceItemTypes['taxes'],
			Invoice::InvoiceItemTypes['regulatory_fee']])->sum('amount');

		$shippingAmount = $order->invoice->invoiceItem->where('description', InvoiceController::SHIPPING)->sum('amount');

		$company = $order->company;
		$customer = $order->customer;
		$payment = $order->payLog;

		$json = [
			"primary_id"    => "BX-".$order->order_num,
			"ordered_at"    => $order->created_at_format,
			"terms"         => $payment->card_type." ".$payment->last4,
			"billing"       => [
				// "subtotal" => " USD",
				"shipping"  => $shippingAmount." USD",
				"tax"       => $taxes." USD",
				"total"     => $order->invoice->subtotal." USD",
			],
			"shipping"      => [
				"ship_to"           => [
					"first_name"    => $order->shipping_fname,
					"last_name"     => $order->shipping_lname,
					"address_1"     => $order->shipping_address1,
					"address_2"     => $order->shipping_address2,
					"city"          => $order->shipping_city,
					"post_code"     => $order->shipping_zip,
					"region"        => $order->shipping_state_id,
					"country"       => "USA",
					'email'         => $customer->email,
					"phone"         =>  $order->customer->phone,
				],
				"ship_from"     => [
					"company"       => $company->name,
					"address_1"     => $company->address_line_1,
					"address_2"     => $company->address_line_2,
					"city"          => $company->city,
					"post_code"     => $company->zip,
					"region"        => $company->state,
					"country"       => "USA",
					"phone"         => $company->support_phone_number
				],
				"ship_type"         => "Priority Mail",
				"ship_via"          => "Stamps.com"
			],
			"boxes"         => $row,
			"source" => [
				"name" => "<value>"
			],
			"message" => "<value>",
			"tags" => [
				"<value>",
				"<value>"
			],
		];

		return json_encode($json);
	}

	/**
	 * @param $data
	 * @param $readyCloudApiKey
	 *
	 * @return false|mixed|\Psr\Http\Message\ResponseInterface
	 * @throws \GuzzleHttp\Exception\GuzzleException
	 */
	public function SentToReadyCloud($data, $readyCloudApiKey)
	{
		try{
			$url = ReadyCloud::getOrgUrl($readyCloudApiKey);
			$url = config('internal.__BRITEX_READY_CLOUD_BASE_URL').$url."orders/"."?bearer_token=".$readyCloudApiKey;
			$client = new Client();
			$response = $client->request('POST', $url, [
				'headers'   => ['Content-type' => 'application/json'],
				'body'      => $data
			]);
			return $response;
		} catch (Exception $e) {
			$msg = 'ReadyCloud exception: '.$e->getMessage();
			\Log::info($msg);
			return false;
		}
	}

}