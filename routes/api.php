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





Route::get('/', function (Request $request) {
    return  response()->json([
            'message' => 'BriteX Backend !!'
        ], 200);
});

Route::middleware('APIToken')->group(function () {


      // Orders API
      Route::group(['prefix' => 'orders', 'namespace' => '\Api\V1'], function()
      {
        Route::get('/', [
          'as' => 'api.orders.list',
          //'middleware' => 'auth:api',
          'uses' => 'OrderController@get',
        ]);
        Route::post('/add', [
          'as' => 'api.orders.add',
          //'middleware' =>'auth:api',
          'uses' => 'OrderController@add',
        ]);
        Route::get('/{id}', [
          'as' => 'api.orders.find',
          //'middleware' => 'auth:api',
          'uses' => 'OrderController@find',
        ]);
        Route::delete('/{id}/delete', [
          'as' => 'api.orders.delete',
          //'middleware' => 'auth:api',
          'uses' => 'OrderController@delete',
        ]);
      });

      Route::group(['prefix' => 'devices', 'namespace' => 'Api\V1'], function()
      {
        Route::get('/',[
          'as' => 'api.devices.list',
          'uses' => 'DeviceController@get',
        ]);
        Route::post('/add',[
         'as' => 'api.devices.add',
        // 'middleware'=>'auth:api',
         'uses' => 'DeviceController@add',
        ]);
        Route::get('/{id}',[
          'as' => 'api.devices.find',
         // 'middleware' => 'auth:api',
          'uses' => 'DeviceController@find',
        ]);
        Route::delete('/{id}/delete',[
        'as' => 'api.devices.delete',
        //'middleware' => 'auth:api',
        'uses' => 'DeviceController@delete',

        ]);
      });

      Route::group(['prefix' => 'plans', 'namespace' => 'Api\V1'],function()
      {
        Route::get('/',[
        'as' => 'api.plans.list',
        'uses' => 'PlanController@get',
        ]);
        Route::post('/add',[
        'as' => 'api.plans.add',
        //'middleware' => 'auth:api',
        'uses' => "PlanController@add",

        ]);
        Route::get('/{id}',[
         'as' => 'api.plans.find',
         //'middleware'=>'auth:api',
         'uses' => 'PlanController@find',
        ]);
        Route::delete('/{id}/delete',[
        'as' => 'api.plans.delete()',
        //'middleware' => 'auth:api',
        'uses' => 'PlanController@delete',
        ]);

      });


      Route::group(['prefix'=>'sims','namespace'=>'Api\v1'],function()
      {
        Route::get('/',[
       'as'=>'api.sims.list',
       'uses'=>'SimController@get',
        ]);
        Route::post('/add',
        [
          'as'=>'api.sims.add',
          //'middleware'=>'auth:api',
          'uses'=>'SimController@add',
        ]);
        Route::get('/{id}',[
        
        'as'=>'api.sims.find',
        //'middleware'=>'auth:api',
        'uses'=>'SimController@find',
        ]);
        Route::delete('/{id}/delete',[
        'as'=>'api.sims.delete',
        //'middleware'=>'auth:api',
        'uses'=>'SimController@delete',
        ]);
      });

      Route::group(['prefix'=>'addons','namespace'=>'Api\v1'],function()
      {
        Route::get('/',[
       'as'=>'api.addons.list',
       'uses'=>'AddonController@get',
        ]);
        Route::post('/add',
        [
          'as'=>'api.addons.add',
          //'middleware'=>'auth:api',
          'uses'=>'AddonController@add',
        ]);
        Route::get('/{id}',[
        
        'as'=>'api.addons.find',
        //'middleware'=>'auth:api',
        'uses'=>'AddonController@find',
        ]);
        Route::delete('/{id}/delete',[
        'as'=>'api.addons.delete',
        //'middleware'=>'auth:api',
        'uses'=>'AddonController@delete',
        ]);
      });

}); //APIToken middleware