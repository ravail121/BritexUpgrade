<?php

use Illuminate\Http\Request;
use App\Events\BusinessVerificationCreated;
use App\Events\InvoiceGenerated;
use App\Events\MonthlyInvoice;
use App\Model\Customer;
use App\Model\Order;
use App\Model\Subscription;
use App\Model\CustomerStandaloneDevice;
use App\Model\CustomerStandaloneSim;

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

Route::get('testing',function()
{
  event(new BusinessVerificationCreated('0c4c659fa09a9b343f4292275614f1cb1567e68f', 'fd2dc9cf7246389ddecca51aacbefe5970e454b8'));
  $order = Order::find('4880');
  event(new InvoiceGenerated($order));
  // $customer = Customer::find('112');
  // event(new MonthlyInvoice($customer));
});

Route::get('insert_order_num',function()
{
  $subscriptions = Subscription::all();
  foreach ($subscriptions as $key => $subscription) {
    $subscription->update(['order_num' => $subscription->order['order_num']]);
  }
  $ccds = CustomerStandaloneDevice::all();
  foreach ($ccds as $key => $ccd) {
    $ccd->update(['order_num' => $ccd->order['order_num']]);
  }
  $ccds = CustomerStandaloneSim::all();
  foreach ($ccds as $key => $ccd) {
    $ccd->update(['order_num' => $ccd->order['order_num']]);
  }
});

Route::get('/cron-jobs-monthly-invoice', [
    'as'=>'api.cron.monthly.invoice',
    'uses'=> 'Api\V1\CronJobs\MonthlyInvoiceController@generateMonthlyInvoice',
]);

Route::get('/cron-jobs-regenerate-invoice', [
  'as'=>'api.cron.regenerate.invoice',
  'uses'=> 'Api\V1\CronJobs\RegenerateInvoiceController@regenerateInvoice',
]);

Route::get('/cron-jobs-orders', [
    'as'=>'api.cron.orders',
    'uses'=> 'Api\V1\CronJobs\OrderController@order',
]);


Route::group(['namespace'=>'Api\V1', 'prefix' => 'cron', 'as' => 'api.cron.'], function(){
	Route::group(['namespace' => 'CronJobs'], function(){
	    Route::get('/process-subscriptions', [
	        'as'=>'api.cron.subscriptions',
	        'uses'=> 'ProcessController@processSubscriptions',
	    ]);
	});
});

 Route::group(['namespace' => '\Api\V1'],function()
{
	Route::post('/sample-image',[
	'as'=>'api.image.post',
	'uses'=>'TestingImageController@post',
	]);
});





Route::get('/', function (Request $request) {
  return  response()->json([
    'message' => 'BriteX Backend !!'
  ], 200);
});

Route::group(['namespace'=>'Api\V1\Invoice'],function(){
  Route::get('/invoice', [
   'as'=>'api.invoice.get',
   'uses'=> 'InvoiceController@get',

  ]);

  Route::get('/sample-invoice', [
   'as'=>'api.sample.invoice',
   'uses'=> 'SampleInvoiceGenerationController@getInvyoice',

  ]);

  Route::get('/sample-statement-invoice', [
   'as'=>'api.sample.statement',
   'uses'=> 'SampleInvoiceGenerationController@getStatement',

  ]);

  Route::get('/invoice/tax-items', [
    'as'=>'api.invoice.getTax',
    'uses'=> 'InvoiceController@getTax',
 
   ]);

   Route::get('/invoice/coupons', [
    'as'=>'api.invoice.getCoupons',
    'uses'=> 'InvoiceController@getCoupons',
 
   ]);
});

Route::group(['namespace'=>'Api\V1', 'prefix' => 'cron', 'as' => 'api.cron.'], function(){
  Route::group(['namespace' => 'CronJobs'], function(){
      Route::get('/process-suspensions', [
          'as'=>'api.cron.suspensions',
          'uses'=> 'ProcessController@processSuspensions',
      ]);
  });
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
        Route::post('/update-shipping', [
          'as' => 'api.order.update.shipping.',
          'uses' => 'OrderController@updateShipping'
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
        Route::get('/state-tax', [
          'as' => 'api.order_group.taxrate'  ,
          'uses' => 'OrderGroupController@taxrate'  
        ]);
        Route::get('/edit-sim', [
          'as' => 'api.order_group.editSim'  ,
          'uses' => 'OrderGroupController@editSim'  
        ]);

      });

      Route::group(['prefix' => 'coupon', 'namespace' => '\Api\V1'], function()
      {
        Route::post('/add-coupon', [
          'as' => 'api.coupon.addCoupon'  ,
          'uses' => 'CouponController@addCoupon'  
        ]);

        Route::post('/remove-coupon', [
          'as' => 'api.remove.removeCoupon',
          'uses' => 'CouponController@removeCoupon'
        ]);

        Route::post('/order-coupon', [
          'as' => 'api.remove.orderCoupon',
          'uses' => 'CouponController@orderCoupon'
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


       Route::group(['prefix' => 'create-customer', 'namespace' => '\Api\V1'],function()
      {
        Route::post('/',[
        'as'=>'api.customer.post',
        'uses'=>'CustomerController@post',
        ]);


        Route::post('/billing-details',[
          'as'=>'api.customer.billing-details',
          'uses'=>'CustomerController@saveBillingDetails',
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
        
        Route::get('/approve', [
            'as'=>'api.bizverification.approve',
            'uses'=>'BizVerificationController@approveBusiness',
 
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

      Route::group(['prefix'=>'support','namespace'=>'Api\V1'],function()
      {
        Route::get('/',[
       'as'=>'api.support.categories',
       'uses'=>'SupportController@get',
        ]);
      });


      Route::group(['namespace'=>'Api\V1\Invoice'],function(){
        // Route::get('/invoice', [
        //  'as'=>'api.invoice.get',
        //  'uses'=> 'InvoiceController@get',

        // ]);

        // Route::get('/sample-invoice', [
        //  'as'=>'api.sample.invoice',
        //  'uses'=> 'SampleInvoiceGenerationController@get',

        // ]);

        Route::get('/cron-jobs', [
         'as'=>'api.monthly.invoice',
         'uses'=> 'MonthlyInvoiceController@generateMonthlyInvoice',

        ]);

        Route::post('/generate-one-time-invoice',[
        'as'   => 'api.onetime.invoice',
        'uses' => 'InvoiceController@oneTimeInvoice',
        ]);

        Route::post('/start-billing',[
          'as'   => 'api.start.billing',
          'uses' => 'InvoiceController@startBilling',
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

        Route::post('/remove-card',[
          'as'   => 'api.add.cards',
          'uses' => 'CardController@removeCard',
        ]);

        Route::post('/charge-card',[
          'as'   => 'api.charge.cards',
          'uses' => 'CardController@chargeCard',
        ]);

        Route::post('/primary-card',[
          'as'   => 'api.charge.cards',
          'uses' => 'CardController@primaryCard',
        ]);

      });



      Route::group(['namespace' => '\Api\V1'],function(){

        Route::post('/create-email-log',[
          'as'   => 'api.create.emaillog',
          'uses' => 'EmailLogController@store'
        ]);

        Route::post('/create-subscription',[
          'as'   => 'api.create.subscription',
          'uses' => 'SubscriptionController@createSubscription',
        ]);

        Route::post('/create-subscription-addon',[
          'as'   => 'api.create.subscriptionaddon',
          'uses' => 'SubscriptionController@subscriptionAddons',
        ]);

        //**NEW
        // Route::post('/update-subscription-addon',[
        //   'as'   => 'api.create.subscriptionaddon',
        //   'uses' => 'SubscriptionController@updateSubscriptionAddon',
        // ]);
        //New**

        Route::post('/create-device-record',[
          'as'   => 'api.create.devicerecord',
          'uses' => 'StandaloneRecordController@createDeviceRecord',
        ]);

        Route::post('/create-sim-record',[
          'as'   => 'api.create.simrecord',
          'uses' => 'StandaloneRecordController@createSimRecord',
        ]);

      });
      

      Route::post('/addon',
      [
        'as'=>'api.addon.',
        'uses'=>'AddonController@add',
      ]);


      Route::group(['namespace' => '\Api\V1'],function(){
        Route::post('/sign-on',[
          'as'   => 'api.customer.signon',
          'uses' => 'SignOnController@signOn',
        ]);

        Route::get('customer',[
          'as'   => 'api.customer.details',
          'uses' => 'CustomerController@customerDetails',
        ]);

        Route::post('customer/account-status',[
          'as'   => 'api.customer.status',
          'uses' => 'CustomerController@accountStatus',
        ]);

        Route::post('/prorated-remaining-days',[
          'as'=>'api.customer.prorated',
          'uses'=>'CustomerController@proratedDays',
        ]);


        Route::post('update-customer',[
          'as'   => 'api.customer.update',
          'uses' => 'CustomerController@update',
        ]);

        Route::get('customer-subscriptions',[
          'as'   => 'api.customer.plan',
          'uses' => 'CustomerPlanController@get',
        ]);

        Route::get('customer-current-invoice',[
          'as'   => 'api.customer.invoice',
          'uses' =>'InvoiceController@invoiceDetail',
        ]);

        Route::get('forgot-password',[
          'as'   => 'api.customer.forgotPassword',
          'uses' =>'ForgotPasswordController@password',
        ]);

        Route::get('check-email',[
          'as'   => 'api.customer.checkEmail',
          'uses' => 'CustomerController@checkEmail',
        ]);

        Route::get('check-password',[
          'as'   => 'api.customer.checkPassword',
          'uses' => 'CustomerController@checkPassword',
        ]);

        Route::get('reset-password',[
          'as'   => 'api.customer.resetPassword',
          'uses' => 'ForgotPasswordController@resetPassword',
        ]);

        Route::get('/customer-orders',[
          'as'   => 'api.customer.orders',
          'uses' => 'CustomerController@customerOrder',
        ]);

        Route::get('/compatible-plans',[
          'as'   => 'api.compatibles.plans',
          'uses' => 'PlanController@compatiblePlans',
        ]);

        Route::get('/compatible-addons',[
          'as'   => 'api.compatibles.addons',
          'uses' => 'PlanController@compatibleAddons',
        ]);

        //NEW API 
        Route::post('/check-plan',[
          'as'   => 'api.check.Plan',
          'uses' => 'PlanController@checkPlan',
        ]);

        Route::post('/update-subscription',[
          'as'   => 'api.update.Subscription',
          'uses' => 'SubscriptionController@updateSubscription',
        ]);

        Route::group([],function(){
          Route::post('/update-port',[
            'as'   => 'api.update.port',
            'uses' => 'CustomerPlanController@updatePort',
          ]);
        });
      }); 


      //Route::get('/confirm','BizVerificationController@confirm');
      
}); //APIToken middleware
