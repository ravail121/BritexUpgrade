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
      Route::group(['prefix' => 'order', 'namespace' => '\Api\V1', 'middleware' => ['JsonApiMiddleware']], function()
      {
        Route::get('/', [
          'as' => 'api.orders.list',
          //'middleware' => 'auth:api',
          'uses' => 'OrderController@get',
        ]);
        Route::post('/', [
          'as' => 'api.orders.post',
          'uses' => 'OrderController@post',
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
        Route::patch('/remove', [
          'as' => 'api.orders.patch_remove',
          'uses' => 'OrderController@remove_from_order',
        ]);
      });

      // Order-Group API
      Route::group(['prefix' => 'order-group', 'namespace' => '\Api\V1'], function()
      {
        Route::get('/', [
          'as' => 'api.order_group.list',
          'uses' => 'OrderGroupController@get',
        ]);
        Route::put('/', [
          'as' => 'api.order_group.put',
          'uses' => 'OrderGroupController@put',
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

        Route::get('/check-area-code',[
         'as'=> 'api.plans.check_area_code',
         'uses'=> "PlanController@check_area_code"
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


      Route::group(['prefix'=>'sims','namespace'=>'Api\V1'],function()
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

      Route::group(['prefix'=>'addons','namespace'=>'Api\V1'],function()
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



       // Porting
      Route::group(['prefix' => 'porting/check', 'namespace' => '\Api\V1'], function()
      {
        Route::get('/', [
          'as' => 'api.porting.check',
          'uses' => 'PortingController@check',
        ]);

      });


       Route::group(['prefix' => 'customer', 'namespace' => '\Api\V1'],function()
      {
        Route::post('/',[
        'as'=>'api.customer.post',
        'uses'=>'CustomerController@post',
        ]);

        /*Route::get('/subscriptions',[
          'as'=>'api.customer.subscription_list',
          'uses'=>'CustomerSubscriptionController@subscription_list',
           
          ]);*/

      }); 


       Route::group(['prefix'=>'biz-verification','namespace'=>'\Api\V1'], function(){
        Route::post('/', [
        'as'=>'api.bizverification.post',
        'uses'=>'BizVerificationController@post',
 
        ]);
      });

        Route::group(['prefix'=>'invoice' , 'namespace'=>'Api\V1\Invoice'],function(){
          Route::get('/', [
           'as'=>'api.invoice.get',
           'uses'=> 'InvoiceController@get',

          ]);

       });

}); //APIToken middleware