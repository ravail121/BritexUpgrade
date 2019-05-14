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

    $config = [
        'driver'   => 'smtp',
        'host'     => 'smtp.mailgun.org',
        'port'     =>  587,
        'username' => 'postmaster@mg.teltik.com',
        'password' => '04e563fba7cb19fad52077c6c91259bd-41a2adb4-8c7d96e1',
    ];

    Config::set('mail',$config);

Route::get('test-email', function(Illuminate\Http\Request $request){

    dd(Mail::raw('Hi There! You Are Awesome.', function ($message) use ($request) {
        $message->from('postmaster@mg.teltik.com');
        $message->to($request->to ?: 'vanak.roopak@gmail.com');
        $message->subject('Email Arrived');
    }));
});



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
        Route::post('/company', [
          'as' => 'api.orders.get_company',
          'uses' => 'OrderController@get_company',
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
        Route::post('/edit', [
          'as' => 'api.order_group.edit',
          'uses' => 'OrderGroupController@edit',
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

      Route::group(['prefix'=>'support','namespace'=>'Api\V1'],function()
      {
        Route::get('/',[
       'as'=>'api.support.categories',
       'uses'=>'SupportController@get',
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


       Route::group(['prefix' => 'create-customer', 'namespace' => '\Api\V1'],function()
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
        
        Route::get('/confirm', [
            'as'=>'api.bizverification.confirm',
            'uses'=>'BizVerificationController@confirm',
 
        ]);

        Route::post('/resend-email', [
            'as'=>'api.bizverification.resendBusinessVerificationEmail',
            'uses'=>'BizVerificationController@resendBusinessVerificationEmail',
        ]);

        Route::delete('/remove-document/{id}', [
            'as'=>'api.bizverification.removeDocument',
            'uses'=>'BizVerificationController@remove_document',
        ]);

      });

      Route::group(['prefix'=>'invoice' , 'namespace'=>'Api\V1\Invoice'],function(){
          Route::get('/', [
           'as'=>'api.invoice.get',
           'uses'=> 'InvoiceController@get',

          ]);

       });

      Route::group(['namespace' => '\Api\V1'],function(){
          Route::get('/default-imei', [
            'as'   => 'api.default.imei',
            'uses' => 'DeviceController@getImei'
          ]);
      });

      Route::group(['namespace' => '\Api\V1'],function(){

          Route::post('/charge-new-card',[
            'as'   => 'api.customer.creditcard',
            'uses' => 'PaymentController@chargeNewCard',
          ]);

          Route::get('/customer-cards',[
            'as'   => 'api.get.customercards',
            'uses' => 'CardController@getCustomerCards',
          ]);

          Route::post('/add-card',[
            'as'   => 'api.add.cards',
            'uses' => 'CardController@addCard',
          ]);

          Route::post('/charge-card',[
            'as'   => 'api.charge.cards',
            'uses' => 'CardController@chargeCard',
          ]);
      });



      Route::group(['namespace' => '\Api\V1'],function(){

          Route::post('/create-subscription',[
            'as'   => 'api.create.subscription',
            'uses' => 'SubscriptionController@createSubscription',
          ]);
      });
      

        Route::post('/confirm',
        [
          'as'=>'api.confirm.',
          //'middleware'=>'auth:api',
          'uses'=>'AddonController@add',
        ]);


        Route::group(['namespace' => '\Api\V1'],function(){
          Route::post('/sign-on',[
            'as'   => 'api.customer.signon',
            'uses' => 'SignOnController@signOn',
          ]);

        }); 


      //Route::get('/confirm','BizVerificationController@confirm');
      

}); //APIToken middleware