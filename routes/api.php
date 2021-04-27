<?php

use Illuminate\Http\Request;
use App\Scripts\TestEye4Fraud;
use App\Events\SubcriptionStatusChanged;
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


Route::get('britex-test-subscription-changed', function(){
	$config = [
		'driver'   => 'smtp',
		'host'     => 'smtp.mailgun.org',
		'port'     =>  587,
		'username' => 'postmaster@mg.teltik.com',
		'password' => '04e563fba7cb19fad52077c6c91259bd-41a2adb4-8c7d96e1',
	];
	Config::set('mail',$config);
	event(new SubcriptionStatusChanged('6'));
});

Route::get('/britex-test-cc', function(){
//	$cc = new TestEye4Fraud();
//	$cc->test();
});

Route::get('britex-test-email', function(Illuminate\Http\Request $request){
	dd(Mail::raw('Hi There! You Are Awesome.', function ($message) use ($request) {
		$message->from('postmaster@mg.teltik.com');
		$message->to($request->to ?: 'prajwal_stha@yahoo.com');
		$message->subject('Email Arrived');
	}));
});

// All Cron URL need to be removed 
// 
Route::get('/auto-pay', [
	'as'=>'api.cron.autopay.invoice',
	'uses'=> 'Api\V1\CardController@autoPayInvoice',
]);

Route::get('/cron-jobs-monthly-invoice', [
	'as'=>'api.cron.monthly.invoice',
	'uses'=> 'Api\V1\CronJobs\MonthlyInvoiceController@generateMonthlyInvoice',
]);

Route::get('/cron-jobs-regenerate-invoice', [
	'as'=>'api.cron.regenerate.invoice',
	'uses'=> 'Api\V1\CronJobs\MonthlyInvoiceController@regenerateInvoice',
]);


Route::get('/cron-jobs-orders/{orderID}', [
	'as'=>'api.cron.orders',
	'uses'=> 'Api\V1\CronJobs\OrderController@order',
]);

Route::get('/cron-jobs-orders-data/{orderID}', [
	'as'=>'api.cron.orders.data',
	'uses'=> 'Api\V1\CronJobs\OrderDataController@order',
]);

Route::get('/update-cron', [
	'as'=>'api.cron.update',
	'uses'=> 'Api\V1\CronJobs\UpdateController@checkUpdates',
]);

// Route::get('/update-cron', [
//   'as'=>'api.cron.update',
//   'uses'=> 'Api\V1\CronJobs\ProcessController@processSubscriptions',
// ]);


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

	Route::get('/invoice/download/{companyId}', [
		'as' => 'api.invoice.download',
		'uses' => 'InvoiceController@get'
	]);

	Route::get('/sample-invoice', [
		'as'=>'api.sample.invoice',
		'uses'=> 'SampleInvoiceGenerationController@getInvoice',

	]);

	Route::get('/sample-statement-invoice', [
		'as'=>'api.sample.statement',
		'uses'=> 'SampleInvoiceGenerationController@getStatement',

	]);

	Route::get('/check-monthly-invoice', [
		'as'=>'api.invoice.get',
		'uses'=> 'InvoiceController@checkMonthlyInvoice',
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

	});

	Route::group(['prefix' => 'coupon', 'namespace' => '\Api\V1'], function()
	{
		Route::post('/add-coupon', [
			'as'    => 'api.coupon.addCoupon'  ,
			'uses'  => 'CouponController@addCoupon'
		]);
		Route::post('/remove-coupon', [
			'as' => 'api.coupon.addCoupon'  ,
			'uses' => 'CouponController@removeCoupon'
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

		Route::post('/email',[
			'as'=>'api.support.email',
			'uses'=>'SupportController@sendEmail',
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

		Route::post('/process-refund',[
			'as'   => 'api.process.refund',
			'uses' => 'PaymentController@processRefund',
		]);

		Route::post('/payment-failed',[
			'as'   => 'api.payment.failed',
			'uses' => 'PaymentController@paymentFailed',
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

		Route::post('/pay-unpaied-invoice',[
			'as'   => 'api.pay.unpaied.invoice',
			'uses' => 'CardController@payCreditToInvoice',
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

		//NEW**
		Route::post('/close-subscription', [
			'as'=>'api.close.subcription',
			'uses'=> 'SubscriptionController@closeSubcription',
		]);

		Route::post('/change-sim', [
			'as'=>'api.change.sim',
			'uses'=> 'SubscriptionController@changeSim',
		]);
		//**

		Route::post('/create-device-record',[
			'as'   => 'api.create.devicerecord',
			'uses' => 'StandaloneRecordController@createDeviceRecord',
		]);

		Route::post('/create-sim-record',[
			'as'   => 'api.create.simrecord',
			'uses' => 'StandaloneRecordController@createSimRecord',
		]);

		Route::post('/validate-sim-num',[
			'as'   => 'api.validate.simnum',
			'uses' => 'SubscriptionController@validateIfTheSimIsUsed',
		]);
	});


	Route::post('/addon',
		[
			'as'=>'api.addon.',
			'uses'=>'AddonController@add',
		]);


	Route::group(['namespace' => '\Api\V1'], function(){
		Route::post('/sign-on',[
			'as'   => 'api.customer.signon',
			'uses' => 'SignOnController@signOn',
		]);

		Route::get('customer',[
			'as'   => 'api.customer.details',
			'uses' => 'CustomerController@customerDetails',
		]);

		Route::post('update-customer',[
			'as'   => 'api.customer.update',
			'uses' => 'CustomerController@update',
		]);

		Route::get('customer-subscriptions',[
			'as'   => 'api.customer.plan',
			'uses' => 'CustomerPlanController@get',
		]);

		Route::get('check-number',[
			'as'   => 'api.check.number',
			'uses' => 'CustomerPlanController@checkNumber',
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
		Route::post('/change-plan',[
			'as'   => 'api.check.Plan',
			'uses' => 'PlanController@checkPlan',
		]);

		Route::post('/update-subscription',[
			'as'   => 'api.update.Subscription',
			'uses' => 'SubscriptionController@updateSubscription',
		]);

		Route::post('/update-sub-label',[
			'as'   => 'api.Subscription.label',
			'uses' => 'SubscriptionController@updateSubLabel',
		]);

		Route::group([], function(){
			Route::post('/update-port',[
				'as'   => 'api.update.port',
				'uses' => 'CustomerPlanController@updatePort',
			]);
		});

		Route::post('/subscription/update-requested-zip',[
			'as'   => 'api.Subscription.requestedZip',
			'uses' => 'SubscriptionController@updateRequestedZip',
		]);
	});


	Route::group(['namespace' => '\Api\V1'],function() {
		Route::get('/subscription-by-phone-number', [
			'as' => 'api.Subscription.phone',
			'uses' => 'SubscriptionController@getSubscriptionByPhoneNumber',
		]);

		Route::post('/subscription-by-id', [
			'as' => 'api.Subscription.id',
			'uses' => 'SubscriptionController@getSubscriptionDetails',
		]);
	});

}); //APIToken middleware
