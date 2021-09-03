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

use App\Model\CustomerCreditCard;
use Carbon\Carbon;

Route::get('/', function () {

//	$expiration = CustomerCreditCard::find(1);
////	dd($expiration->expiration);
//	$expiration = Carbon::createFromFormat('ny', $expiration->expiration);
////	dd($expiration);
//	$twoMonthsPriorDate = (int) $expiration->copy()->addMonth(2)->format('ny');
//	$oneMonthPriorDate = (int) $expiration->copy()->addMonth()->format('ny');
//	$twoMonthsPriorDate = Carbon::today()->addMonth(2)->format('ny');
//	$oneMonthPriorDate = Carbon::today()->addMonth()->format('ny');
//	$twoMonthsPriorDate = '1222';
//	$oneMonthPriorDate = '1122';

//	$customerCreditCards = CustomerCreditCard::where('expiration', $twoMonthsPriorDate)
//	                                         ->orWhere('expiration', $oneMonthPriorDate)
//	                                         ->with('customer')->get();
    return  response()->json([
        'message'   => 'BriteX Backend !!',
	    'data'      => [
//	    	'today'     => $today,
		    '2_month'   => $twoMonthsPriorDate,
		    '1_month'   => $oneMonthPriorDate,
		    'expiration'    => $expiration

	    ]
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
