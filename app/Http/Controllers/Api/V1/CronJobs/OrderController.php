<?php

namespace App\Http\Controllers\Api\V1\CronJobs;

use Exception;
use ShippingEasy;
use App\Model\Sim;
use Carbon\Carbon;
use App\Model\Order;
use App\Model\Device;
use App\Model\CronLog;
use GuzzleHttp\Client;
use App\Model\Invoice;
use ShippingEasy_Order;
use App\Http\Modules\ReadyCloud;
use App\Http\Controllers\BaseController;
use App\Http\Controllers\Api\V1\Traits\CronLogTrait;
use App\Http\Controllers\Api\V1\Invoice\InvoiceController;

/**
 * Class OrderController
 *
 * @package App\Http\Controllers\Api\V1\CronJobs
 */
class OrderController extends BaseController
{
	use CronLogTrait;

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
			$orders = Order::where('status', '1')->with('subscriptions', 'standAloneDevices', 'standAloneSims', 'customer', 'invoice.invoiceItem', 'payLog')->whereHas('subscriptions', function($subscription) {
				$subscription->where([['status', 'shipping'],['sent_to_readycloud', 0 ], ['sent_to_shipping_easy', 0]]);
			})->orWhereHas('standAloneDevices', function($standAloneDevice) {
				$standAloneDevice->where([['status', 'shipping'],['processed', 0 ]]);
			})->orWhereHas('standAloneSims', function($standAloneSim) {
				$standAloneSim->where([['status', 'shipping'],['processed', 0 ]]);
			})->with('company')->get();
		}

		try {
			foreach ($orders as $order) {
				$readyCloudApiKey        = $order->company->readycloud_api_key;
				$shippingEasyApiKey      = $order->company->shipping_easy_api_key;
				$shippingEasySecret      = $order->company->shipping_easy_api_secret;
				$shippingEasyStoreApiKey = $order->company->shipping_easy_store_api_key;
				if ( $readyCloudApiKey == null && $shippingEasyApiKey == null && $shippingEasySecret == null && $shippingEasyStoreApiKey == null ) {
					continue;
				}

				$subscriptionRow     = [];
				$standAloneDeviceRow = [];
				$standAloneSimRow    = [];


				if ( $shippingEasyApiKey && $shippingEasySecret && $shippingEasyStoreApiKey && $order->invoice && $order->invoice->invoiceItem ) {
					foreach ( $order->subscriptions as $key => $subscription ) {
						$responseData = $this->subscriptionsForShippingEasy( $subscription, $order->invoice->invoiceItem );

						if ( $responseData ) {
							$subscriptionRow[ $key ] = $responseData;
						}
					}
					foreach ( $order->standAloneDevices as $key => $standAloneDevice ) {
						$standAloneDeviceRow[ $key ] = $this->standAloneDeviceForShippingEasy( $standAloneDevice, $order->invoice->invoiceItem );
					}

					foreach ( $order->standAloneSims as $key => $standAloneSim ) {
						$standAloneSimRow[ $key ] = $this->standAloneSimForShippingEasy( $standAloneSim, $order->invoice->invoiceItem );
					}
					$lineItems = array_merge( $subscriptionRow, $standAloneDeviceRow, $standAloneSimRow );

					$shippingEasyApiData = $this->shippingEasyData( $order, $lineItems );

					$response            = $this->sendToShippingEasy( $shippingEasyApiData, $shippingEasyApiKey, $shippingEasySecret, $shippingEasyStoreApiKey );

					if ( $response && $response['order']) {

						$order->subscriptions()->update( [ 'sent_to_shipping_easy' => 1 ] );
						$order->standAloneDevices()->update( [ 'processed' => 1 ] );
						$order->standAloneSims()->update( [ 'processed' => 1 ] );
						$logEntry = [
							'name'     => CronLog::TYPES[ 'ship-order' ],
							'status'   => 'success',
							'payload'  => json_encode( $shippingEasyApiData ),
							'response' => 'Order shipped for ' . $order->id
						];

						$this->logCronEntries( $logEntry );
					} else {
						$logEntry = [
							'name'     => CronLog::TYPES[ 'ship-order' ],
							'status'   => 'error',
							'payload'  => json_encode( $shippingEasyApiData ),
							'response' => 'Order ship failed for ' . $order->id
						];

						$this->logCronEntries( $logEntry );

						return $this->respond( [ 'message' => 'Something went wrong!' ] );
					}
				} else {
					if($order->invoice && $order->invoice->invoiceItem) {

						foreach ( $order->subscriptions as $key => $subscription ) {
							$responseData = $this->subscriptions( $subscription, $order->invoice->invoiceItem );
							if ( $responseData ) {
								$subscriptionRow[ $key ] = $responseData;
							}
						}

						foreach ( $order->standAloneDevices as $key => $standAloneDevice ) {
							$standAloneDeviceRow[ $key ] = $this->standAloneDevice( $standAloneDevice, $order->invoice->invoiceItem );
						}

						foreach ( $order->standAloneSims as $key => $standAloneSim ) {
							$standAloneSimRow[ $key ] = $this->standAloneSim( $standAloneSim, $order->invoice->invoiceItem );
						}
						$row[ 0 ][ 'items' ] = array_merge( $subscriptionRow, $standAloneDeviceRow, $standAloneSimRow );
						$apiData             = $this->data( $order, $row );
						$response            = $this->SentToReadyCloud( $apiData, $readyCloudApiKey );
						if ( $response ) {
							if ( $response->getStatusCode() == 201 ) {
								$order->subscriptions()->update( [ 'sent_to_readycloud' => 1 ] );
								$order->standAloneDevices()->update( [ 'processed' => 1 ] );
								$order->standAloneSims()->update( [ 'processed' => 1 ] );
								$logEntry = [
									'name'     => CronLog::TYPES[ 'ship-order' ],
									'status'   => 'success',
									'payload'  => json_encode( $apiData ),
									'response' => 'Order shipped for ' . $order->id
								];

								$this->logCronEntries( $logEntry );
							} else {
								$logEntry = [
									'name'     => CronLog::TYPES[ 'ship-order' ],
									'status'   => 'error',
									'payload'  => json_encode( $apiData ),
									'response' => 'Order ship failed for ' . $order->id
								];

								$this->logCronEntries( $logEntry );

								return $this->respond( [ 'message' => 'Something went wrong!' ] );
							}
						}
					}
				}
			}
		}catch (Exception $e) {
			$logEntry = [
				'name'      => CronLog::TYPES['ship-order'],
				'status'    => 'error',
				'payload'   => '',
				'response'  => $e->getMessage()
			];

			$this->logCronEntries($logEntry);
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
		}
	}

	/**
	 * @param $subscription
	 * @param $invoiceItem
	 *
	 * @return string[]|void
	 */
	public function subscriptionsForShippingEasy($subscription, $invoiceItem){
		if(($subscription->sim_id != 0) && ($subscription->device_id == 0)) {
			return  $this->subscriptionWithSimForShippingEasy($subscription, $invoiceItem);
		}

		if(($subscription->device_id != 0) && ($subscription->sim_id == 0)) {
			return  $this->subscriptionWithDeviceForShippingEasy($subscription, $invoiceItem);
		}

		if(($subscription->sim_id != 0) && ($subscription->device_id != 0)) {
			return $this->subscriptionWithSimAndDeviceForShippingEasy($subscription, $invoiceItem);
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
	public function subscriptionWithSimForShippingEasy($subscription, $invoiceItem)
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
			'item_name'             => $subscription->sim_name . ' ($'. $subscription->sim['amount_w_plan'] . ') associated with '. $subscription->plan['name'] . ' ($' . $planAmount . ')',
			'sku'                   => 'SUB-'.$subscription->id,
			'unit_price'            => $amount,
			'quantity'              => '1',
			'total_excluding_tax'   => $amount,
			'price_excluding_tax'   => $amount,
			'product_options'       => [
				'sim_card_num' => '',
			]
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
	public function subscriptionWithDeviceForShippingEasy($subscription, $invoiceItem)
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
			'item_name'             => $subscription->device['name'] . ' ($'. $subscription->device['amount_w_plan'] .') associated with ' . $subscription->plan['name'] .' ($' . $planAmount .')',
			'sku'                   => 'SUB-'.$subscription->id,
			'unit_price'            => $amount,
			'quantity'              => '1',
			'total_excluding_tax'   => $amount,
			'price_excluding_tax'   => $amount,
			'product_options'       => [
				'imei_no' => '',
			]
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
	 * @param $subscription
	 * @param $invoiceItem
	 *
	 * @return string[]
	 */
	public function subscriptionWithSimAndDeviceForShippingEasy($subscription, $invoiceItem)
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
			'item_name'             => $subscription->sim_name . ' ($'. $subscription->sim['amount_w_plan'] . ') associated with ' . $subscription->plan['name'] . ' ($' . $planAmount . ') and ' . $subscription->device['name'] . ' ($' . $subscription->device['amount_w_plan'] . ')',
			'sku'                   => 'SUB-'.$subscription->id,
			'unit_price'            => $amount,
			'quantity'              => '1',
			'total_excluding_tax'   => $amount,
			'price_excluding_tax'   => $amount,
			'product_options'       => [
				'sim_card_num'  => '',
				'imei_no'       => '',
			]
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
		return [
			'description'   => $standAloneDevice->device['name'] . ' ($' . $amount . ')',
			'part_number'   => 'DEV-'.$standAloneDevice->id,
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
	public function standAloneDeviceForShippingEasy($standAloneDevice, $invoiceItem)
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
		return [
			'item_name'             => $standAloneDevice->device['name'] . ' ($' . $amount . ')',
			'sku'                   => 'DEV-'.$standAloneDevice->id,
			'unit_price'            => $amount,
			'quantity'              => '1',
			'total_excluding_tax'   => $amount,
			'price_excluding_tax'   => $amount,
			'product_options'       => [
				'imei_no'       => '',
			]
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
		foreach ($invoiceItemAmount->toArray() as $value) {
			$amount = $value['amount'];
			break;
		}
		return [
			'description'   => $standAloneSim->sim['name']. ' ($' . $amount . ')',
			'part_number'   => 'SIM-'.$standAloneSim->id,
			'unit_price'    => $amount.' USD',
			'quantity'      => '1'
		];

	}


	/**
	 * @param $standAloneSim
	 * @param $invoiceItem
	 *
	 * @return string[]
	 */
	public function standAloneSimForShippingEasy($standAloneSim, $invoiceItem)
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
		foreach ($invoiceItemAmount->toArray() as $value) {
			$amount = $value['amount'];
			break;
		}
		return [
			'item_name'             => $standAloneSim->sim['name']. ' ($' . $amount . ')',
			'sku'                   => 'SIM-'. $standAloneSim->id,
			'unit_price'            => $amount,
			'quantity'              => '1',
			'total_excluding_tax'   => $amount,
			'price_excluding_tax'   => $amount,
			'product_options'       => [
				'sim_card_num'       => '',
			]
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
			"primary_id"    => "BX".$company->id. "-".$order->order_num,
			"ordered_at"    => $order->updated_at_format,
			"terms"         => $payment->card_type." ".$payment->last4,
			"billing"       => [
				// "subtotal" => " USD",
				"shipping"  => $shippingAmount." USD",
				"tax"       => $taxes." USD",
				"total"     => $order->invoice->subtotal." USD",
			],
			"shipping"      => [
				"ship_to"           => [
					"first_name"     => $order->shipping_fname,
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
	 * @param $order
	 * @param $lineItems
	 *
	 * @return array
	 */
	public function shippingEasyData($order, $lineItems)
	{
		$taxes = $order->invoice->invoiceItem->whereIn('type', [Invoice::InvoiceItemTypes['taxes'],
			Invoice::InvoiceItemTypes['regulatory_fee']])->sum('amount');

		$shippingAmount = $order->invoice->invoiceItem->where('description', InvoiceController::SHIPPING)->sum('amount');

		$company = $order->company;
		$customer = $order->customer;

		return [
			"external_order_identifier"      => "BX".$company->id. "-".$order->order_num."-".time(),
			"subtotal_including_tax"        => $order->invoice->subtotal,
			"ordered_at"                    => $this->formatDateForShippingEasy($order->updated_at),
			"discount_amount"               => $order->invoice->cal_credits,
			"total_including_tax"           => $order->invoice->subtotal,
			"total_excluding_tax"           => $order->invoice->subtotal - $taxes,
			"coupon_discount"               => $order->invoice->cal_used_coupon_discount,
			"subtotal_excluding_tax"        => $order->invoice->subtotal - $taxes,
			"subtotal_tax"                  => $taxes,
			"total_tax"                     => $taxes,
			"base_shipping_cost"            => $shippingAmount,
			"shipping_cost_including_tax"   => $shippingAmount,
			"shipping_cost_excluding_tax"   => $shippingAmount,
			"shipping_cost_tax"             => "0.00",
			"recipients"                    => [
				[
					"first_name"             => $order->shipping_fname,
					"last_name"             => $order->shipping_lname,
					"company"               => $customer->company_name,
					"email"                 => $customer->email,
					"phone_number"          => $order->customer->phone,
					"residential"           => "true",
					"address"               => $order->shipping_address1,
					"address2"              => $order->shipping_address2,
					"province"              => "",
					"state"                 => $order->shipping_state_id,
					"city"                  => $order->shipping_city,
					"postal_code"           => $order->shipping_zip,
					"postal_code_plus_4"    => $order->shipping_zip,
					"country"               => "US",
					"shipping_method"       => "Ground",
					"items_total"           => count($lineItems) ? count($lineItems[0]) : 0,
					"line_items"            => $lineItems
				]
			]
        ];
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

	/**
	 * @param $data
	 * @param $shippingEasyApiKey
	 * @param $shippingEasyApiSecret
	 * @param $shippingEasyStoreApiKey
	 *
	 * @return false|mixed
	 */
	public function sendToShippingEasy($data, $shippingEasyApiKey, $shippingEasyApiSecret, $shippingEasyStoreApiKey)
	{
		try{
			ShippingEasy::setApiKey($shippingEasyApiKey);
			ShippingEasy::setApiSecret($shippingEasyApiSecret);
			$shippingEasyOrder = new ShippingEasy_Order($shippingEasyStoreApiKey, $data);
			return $shippingEasyOrder->create();
		} catch (Exception $e) {
			$msg = 'ShippingEasy exception: '.$e->getMessage();
			\Log::info($msg);
			return false;
		}
	}

	/**
	 * @param $date
	 *
	 * @return string
	 */
	private function formatDateForShippingEasy($date)
	{
		return Carbon::parse($date)->format('Y-m-d H:i:s O');
	}

}