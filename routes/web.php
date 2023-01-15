<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

// Route::get('logs', '\Rap2hpoutre\LaravelLogViewer\LogViewerController@index');

use App\Http\Controllers\Api\V1\CronJobs\OrderController;
use App\Model\CronLog;
use App\Model\Order;

Route::get('/', function () {
    return  response()->json([
        'message'   => 'BriteX Backend !!'
    ]);
});

/**
Route::get('britex-test-subscription-changed', function(){
	$config = [
		'driver'   => 'smtp',
		'host'     => 'smtp.mailgun.org',
		'port'     =>  587,
		'username' => 'postmaster@mg.teltik.com',
		'password' => '04e563fba7cb19fad52077c6c91259bd-41a2adb4-8c7d96e1',
	];
	Config::set('mail',$config);
	event(new SubcriptionStatusChanged('702'));
});
 **/

Route::get('britex-test', function(){
	$order = Order::find(71090);
	$orderController = new OrderController();
	$readyCloudApiKey = $order->company->readycloud_api_key;
	if($order->invoice && $order->invoice->invoiceItem) {

		foreach ( $order->subscriptions as $key => $subscription ) {
			$responseData = $orderController->subscriptions( $subscription, $order->invoice->invoiceItem );
			if ( $responseData ) {
				$subscriptionRow[ $key ] = $responseData;
			}
		}

		foreach ( $order->standAloneDevices as $key => $standAloneDevice ) {
			$standAloneDeviceRow[ $key ] = $orderController->standAloneDevice( $standAloneDevice, $order->invoice->invoiceItem );
		}

		foreach ( $order->standAloneSims as $key => $standAloneSim ) {
			$standAloneSimRow[ $key ] = $orderController->standAloneSim( $standAloneSim, $order->invoice->invoiceItem );
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
});