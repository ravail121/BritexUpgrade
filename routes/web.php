<?php

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

Route::get('/test-subscription-null', 'Api\V1\CronJobs\SubscriptionStatusDateController@processAccountSuspendedAndNullStartDateCheck');
