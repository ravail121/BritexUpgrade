<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// Route::middleware('auth:api')->get('/user', function (Request $request) {
//     return $request->user();
// });


// Signal API
Route::get('/', function (Request $request) {
    //return $request->json(['status' => 'connected']);
     return  response()->json([
            'message' => 'BriteX Backend !!'
        ], 200);
});


// Orders API
Route::group(['prefix' => 'orders', 'namespace' => '\Api\V1'], function()
{
  Route::get('/', [
    'as' => 'api.orders.list',
    //'middleware' => 'auth:api',
    'uses' => 'OrderController@get',
  ]);
  // Route::post('/add', [
  //   'as' => 'api.orders.add',
  //   'middleware' => 'auth:api',
  //   'uses' => 'OrderController@add',
  // ]);
  // Route::get('/{id}', [
  //   'as' => 'api.orders.find',
  //   'middleware' => 'auth:api',
  //   'uses' => 'OrderController@find',
  // ]);
  // Route::delete('/{id}/delete', [
  //   'as' => 'api.orders.delete',
  //   'middleware' => 'auth:api',
  //   'uses' => 'OrderController@delete',
  // ]);
});
