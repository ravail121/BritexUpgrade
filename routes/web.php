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

use App\Model\Addon;
use App\Model\Device;
use App\Model\Invoice;
use App\Model\InvoiceItem;
use App\Model\Sim;

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

Route::group(['namespace'=>'Api\V1\CronJobs'],function() {
	Route::get( '/test-shipping-easy', 'OrderController@order' );

});


Route::get('/britex-test-test', function() {
	$breakdowns = '';
	$standAloneItems = Invoice::find( 3752 )->invoiceItem()->where( 'subscription_id', null )->orWhere( 'subscription_id', 0 )->get();
	$standAloneDevices = $standAloneItems->where( 'product_type', InvoiceItem::PRODUCT_TYPE[ 'device' ] );
	$standAloneSims = $standAloneItems->where('product_type', InvoiceItem::PRODUCT_TYPE['sim']);
	$standAloneAddons = $standAloneItems->where('product_type', InvoiceItem::PRODUCT_TYPE['addon']);
	$groupedAddons = $standAloneAddons->groupBy(function($standAloneAddon) {
		return $standAloneAddon->product_id;
	});

	$groupedSims = $standAloneSims->groupBy(function($standAloneSim) {
		return $standAloneSim->product_id;
	});

	$groupedDevices = $standAloneDevices->groupBy(function($standAloneDevice) {
		return $standAloneDevice->product_id;
	});

	$groupedAddons = $standAloneAddons->groupBy(function($standAloneAddon) {
		return $standAloneAddon->product_id;
	});



	if($groupedSims->count() > 0) {
		foreach($groupedSims as $simId => $sim) {
			$simRecord = Sim::find($simId);
			if($simRecord) {
				$groupedPrice = $simRecord->amount_alone * $sim->count();
				$breakdowns   .= $simRecord->name . ' : $ ' . number_format( $groupedPrice ) . ', ';
			}
		}
	}

	if($groupedDevices->count() > 0) {
		foreach($groupedDevices as $deviceId => $device) {
			$deviceRecord = Device::find($deviceId);
			if($deviceRecord) {
				$groupedPrice = $deviceRecord->amount * $device->count();
				$breakdowns   .=  $deviceRecord->name . ' : $ ' . number_format( $groupedPrice ) . ', ';
			}
		}
	}
	dd(trim($breakdowns, ', '));
});
