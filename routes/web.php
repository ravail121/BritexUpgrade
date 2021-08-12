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


use Carbon\Carbon;

Route::get('/', function () {
    return  response()->json([
        'message'   => 'BriteX Backend !!'
    ]);
});

Route::get('/test-dates', function() {
	$current_date = Carbon::createFromFormat('d/m/Y',  '02/01/2022');
	$subscription_start_date = Carbon::createFromFormat('d/m/Y',  '01/02/2022');
	$monthAddition = (int) $subscription_start_date->diffInMonths($current_date) + 1;
//	return $customerSubscriptionStartDate->addMonthsNoOverflow($monthAddition)->subDay()->toDateString();
	return  response()->json([
		'message'   => $current_date->toDateString(),
		'month_addition'    => $monthAddition,
		'subs_start_date'   => $subscription_start_date->toDateString(),
		'end_date'  => $subscription_start_date->addMonthsNoOverflow($monthAddition)->subDay()->toDateString()
	]);
//	'subscription_start_date' => $carbon->toDateString(),
//                    'billing_start'           => $this->carbon->toDateString(),
//                    'billing_end'             => $this->carbon->addMonthsNoOverflow(1)->subDay()->toDateString()

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
