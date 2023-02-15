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


Route::get('/', function (Request $request) {
	return  response()->json([
		'message' => 'BriteX Backend !!'
	], 200);
});

// Route::get('/test-general-job', [
// 	'as'=>'api.cron.general',
// 	'uses'=> 'Api\V1\CronJobs\GeneralJobController@generalCronJob',
// ]);

Route::get('/test', [
	'as'=>'api.cron.data.usage2',
	'uses'=> 'Api\V1\CronJobs\DataUsage@getUsageData2',
]);


Route::get('/data_usage', [
	'as'=>'api.cron.data.usage',
	'uses'=> 'Api\V1\CronJobs\DataUsage@getUsageData',
]);

Route::post('/check2', [
	'as'=>'api.check.data.usage',
	'uses'=> 'Api\V1\CronJobs\DataUsage@check2',
]);

Route::group(['namespace'=>'Api\V1\Invoice'],function(){

	Route::get('/cron-jobs', [
		'as'=>'api.monthly.invoice',
		'uses'=> 'MonthlyInvoiceController@generateMonthlyInvoice',
	]);

	Route::post('/generate-one-time-invoice',[
		'as'   => 'api.onetime.invoice',
		'uses' => 'InvoiceController@oneTimeInvoice',
	]);

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


Route::middleware('APIToken')->group(function () {
	Route::get('/get-sims-telit', [
		'as'=>'api.cron.auth',
		'uses'=> 'Api\V1\CronJobs\APICallController@callAuthentication',
	]);
	Route::get('/get-plans-telit', [
		'as'=>'api.cron.plans',
		'uses'=> 'Api\V1\CronJobs\APICallController@getPlans',
	]);

	Route::post('/activation/notify-error', [
		'as'=>'api.activation.notify.error',
		'uses'=> 'Api\V1\ActivationController@sendNotification',
	]);

	// Orders API
	Route::group(['prefix' => 'order', 'namespace' => 'Api\V1', 'middleware' => ['JsonApiMiddleware']], function()
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
	Route::group(['prefix' => 'order-group', 'namespace' => 'Api\V1'], function()
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

	Route::group(['prefix' => 'coupon', 'namespace' => 'Api\V1'], function()
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
		Route::post('/updateOrder',[
			'as' => 'api.devices.order',
			'uses' => 'DeviceController@updateOrder',
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


	Route::group(['prefix' => 'sims', 'namespace' => 'Api\V1'], function()
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

	Route::group(['prefix' => 'addons', 'namespace' => 'Api\V1'], function()
	{
		Route::get('/', [
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
			'as' => 'api.addons.find',
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
	Route::group(['prefix' => 'porting/check', 'namespace' => 'Api\V1'], function()
	{
		Route::get('/', [
			'as' => 'api.porting.check',
			'uses' => 'PortingController@check',
		]);
	});


	Route::group(['prefix' => 'create-customer', 'namespace' => 'Api\V1'],function()
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


	Route::group(['prefix' => 'biz-verification', 'namespace' => 'Api\V1'], function(){
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

	Route::group(['prefix' => 'support', 'namespace' => 'Api\V1'], function()
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


	Route::group(['namespace'=>'Api\V1\Invoice'], function(){
		// Route::get('/invoice', [
		//  'as'=>'api.invoice.get',
		//  'uses'=> 'InvoiceController@get',

		// ]);

		Route::post('/start-billing',[
			'as'   => 'api.start.billing',
			'uses' => 'InvoiceController@startBilling',
		]);
	});

	Route::group(['namespace' => 'Api\V1'], function(){

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

		Route::post('/query-active-sim-with-addon',[
			'as'   => 'api.query.active-sim-with-addon',
			'uses' => 'SubscriptionController@queryActiveSubscriptionWithAddon',
		]);

		Route::get('/blogs',[
			'as' => 'api.blogs.list',
			'uses' => 'BlogsController@get',
		]);

		Route::post('/deleteBlogById',[
			'as' => 'api.blogs.id',
			'uses' => 'BlogsController@deleteBlogById',
		]);

		Route::post('/blogs',[
			'as' => 'api.blogs.post',
			'uses' => 'BlogsController@post',
		]);

		Route::post('/removeImageById',[
			'as' => 'api.blogs.id',
			'uses' => 'BlogsController@removeImageById',
		]);

		Route::post('/blogsById',[
			'as' => 'api.blogs.id',
			'uses' => 'BlogsController@blogsById',
		]);
		Route::post('/edit-blog-by-id',[
			'as' => 'api.blogs.post',
			'uses' => 'BlogsController@update',
		]);


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

		Route::any('/customer-cards',[
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

		Route::get('/default-imei', [
			'as'   => 'api.default.imei',
			'uses' => 'DeviceController@getImei'
		]);
	});


	Route::post('/addon',
		[
			'as'=>'api.addon.',
			'uses'=>'AddonController@add',
		]);


	Route::group(['namespace' => 'Api\V1'], function(){
		Route::post('/sign-on',[
			'as'   => 'api.customer.signon',
			'uses' => 'SignOnController@signOn',
		]);

		Route::any('customer',[
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

		Route::post('/update-port',[
			'as'   => 'api.update.port',
			'uses' => 'CustomerPlanController@updatePort',
		]);

		Route::post('/subscription/update-requested-zip',[
			'as'   => 'api.Subscription.requestedZip',
			'uses' => 'SubscriptionController@updateRequestedZip',
		]);

		Route::post('/customers',[
			'as'   => 'api.customers.list',
			'uses' => 'CustomerController@listCustomers',
		]);

		Route::get('/subscription-by-phone-number', [
			'as' => 'api.Subscription.phone',
			'uses' => 'SubscriptionController@getSubscriptionByPhoneNumber',
		]);

		Route::post('/subscription-by-id', [
			'as' => 'api.Subscription.id',
			'uses' => 'SubscriptionController@getSubscriptionDetails',
		]);
	});

	/**
	 * APIS for Bulk Orders
	 */
	Route::group(['prefix' => 'bulk-order', 'namespace' => 'Api\V1'], function() {
		Route::post( '/customer', [
			'as'   => 'api.bulk.order.create.customer',
			'uses' => 'CustomerController@createCustomerForBulkOrder',
		]);

		Route::post( '/order', [
			'as'   => 'api.bulk.order.create.order',
			'uses' => 'OrderController@createOrderForBulkOrder',
		]);

		Route::post( '/list-order-details', [
			'as'    => 'api.bulk.order.list.details',
			'uses'  => 'OrderController@listCustomerSimOrder'
		]);

		Route::post( '/preview', [
			'as'   => 'api.bulk.order.preview.order',
			'uses' => 'OrderController@previewOrderForBulkOrder',
		]);

		Route::post( '/close-lines', [
			'as'   => 'api.bulk.order.close.lines',
			'uses' => 'OrderController@closeSubscriptionForBulkOrder',
		]);

		Route::post( '/open-lines', [
			'as'   => 'api.bulk.order.open.lines',
			'uses' => 'OrderController@openSubscriptionForBulkOrder',
		]);

		Route::post( '/list-customer-id', [
			'as'   => 'api.bulk.list.customer.id',
			'uses' => 'OrderController@listCustomerIdFromAssignedSIMForBulkOrder',
		]);
		Route::post( '/activate-subscription', [
			'as'   => 'api.bulk.activate.subscription',
			'uses' => 'SubscriptionController@activateSubscription',
		]);

		Route::post( '/list-sims', [
			'as'   => 'api.bulk.list.sims',
			'uses' => 'BulkOrder\CheckoutController@simsForCatalogue',
		]);

		Route::post( '/create-bulk-order', [
			'as'   => 'api.bulk.order.create.bulk-order',
			'uses' => 'BulkOrder\CheckoutController@createOrder',
		]);

		Route::post( '/order-summary', [
			'as'   => 'api.bulk.order.preview.order-summary',
			'uses' => 'BulkOrder\CheckoutController@orderSummaryForBulkOrder'
		]);

		Route::post( '/list-order-sims', [
			'as'   => 'api.bulk.order.list.order-sims',
			'uses' => 'BulkOrder\CheckoutController@listOrderSims'
		]);

		Route::post( '/list-order-plans', [
			'as'   => 'api.bulk.order.list.order-plans',
			'uses' => 'BulkOrder\CheckoutController@listOrderPlans'
		]);

		Route::post('/csv-order-subscriptions', [
			'as'   => 'api.bulk.order.csv-order-subscriptions',
			'uses' => 'BulkOrder\CheckoutController@csvOrderSubscriptions'
		]);

		Route::post('/order-subscriptions', [
			'as'   => 'api.bulk.order.order-subscriptions',
			'uses' => 'BulkOrder\CheckoutController@orderSubscriptions'
		]);

		Route::post('/get-orders', [
			'as'   => 'api.bulk.order.get-orders',
			'uses' => 'BulkOrder\CheckoutController@getOrders'
		]);

		Route::post('/generate-one-time-invoice', [
			'as'   => 'api.bulk.order.generate-one-time-invoice',
			'uses' => 'BulkOrder\CheckoutController@generateOneTimeInvoice'
		]);

		Route::post('/close-lines', [
			'as'   => 'api.bulk.order.close-lines',
			'uses' => 'BulkOrder\CheckoutController@closeLines'
		]);

		Route::post('/ultra/validate-sim-number', [
			'as'   => 'api.bulk.order.ultra.validate-sim',
			'uses' => 'BulkOrder\CheckoutController@validateZipCodeForUltraSims'
		]);
	});

	/**
	* APIS for Subscription Logs
    */
	Route::group(['prefix' => 'subscription-log', 'namespace' => 'Api\V1'], function() {
		Route::post( '/store', [
			'as'   => 'api.subscription.log.create.subscription.log',
			'uses' => 'SubscriptionLogController@store',
		] );
	});
}); //APIToken middleware


Route::middleware('ShippingEasyCallbackAuthenticator')->group(function () {
	Route::group(['prefix' => 'shipment', 'namespace' => '\Api\V1', 'middleware' => ['JsonApiMiddleware']], function() {
		Route::post( '/callback', [
			'as'   => 'api.shipment.callback',
			'uses' => 'ShippingEasyShipmentNotificationCallback@updateOrderShipment',
		] );
	});
	// Shipment Callback
});

