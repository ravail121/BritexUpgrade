<?php

use App\Events\SubcriptionStatusChanged;
use Illuminate\Http\Request;
use App\Model\CouponProduct;
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

// Route::get('/', function () {
//     return view('welcome');
// });

Route::get('logs', '\Rap2hpoutre\LaravelLogViewer\LogViewerController@index');

Route::get('/', function (Request $request) {
    return  response()->json([
      'message' => 'BriteX Backend !!'
    ], 200);
});

Route::get('/test', function() {
    return "TEST";
});

Route::get('/test-subs', function() {
	return "TEST";
});


Route::get('britex-test-subscription-changed', function(){
//	$config = [
//		'driver'   => 'smtp',
//		'host'     => 'smtp.mailgun.org',
//		'port'     =>  587,
//		'username' => 'postmaster@mg.teltik.com',
//		'password' => '04e563fba7cb19fad52077c6c91259bd-41a2adb4-8c7d96e1',
//	];
//	Config::set('mail',$config);
	event(new SubcriptionStatusChanged('64'));
});
