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

Route::get('/', function () {
    return  response()->json([
        'message'   => 'BriteX Backend !!'
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


Route::get('/test-shipping-easy', function () {
	ShippingEasy::setApiKey('3b6b5e06335a1fc8ee619c1b30a66d2f');
	ShippingEasy::setApiSecret('49b1d3b8b708946b03ba2e95aac81bf64ef4fca680e7cc67f61403dbab2e0adc');
	$shippingEasyOrder = new ShippingEasy_Order('b537f50bc5654213566d05d51fbca2a9',
		array(
			"external_order_identifier" => "390049",
          "subtotal_including_tax" => "12.38",
          "ordered_at" => "2019-10-21 08:51:00 -0500",
          "notes" => "Here is the customer note",
          "internal_notes" => "Here is the internal note",
          "custom_1" => "Custom Field 1",
          "custom_2" => "Custom Field 2",
          "custom_3" => "Custom Field 3",
          "total_including_tax" => "12.63",
          "total_excluding_tax" => "10.00",
          "discount_amount" => "0.00",
          "coupon_discount" => "0.00",
          "subtotal_excluding_tax" => "10.00",
          "subtotal_tax" => "0.00",
          "total_tax" => "0.00",
          "base_shipping_cost" => "2.63",
          "shipping_cost_including_tax" => "2.63",
          "shipping_cost_excluding_tax" => "2.63",
          "shipping_cost_tax" => "0.00",
          "recipients" => array(
              array(
                  "first_name" => "Jack",
                  "last_name" => "Ship",
                  "company" => "ShippingEasy",
                  "email" => "jack@shippingeasy.com",
                  "phone_number" => "855-202-2275",
                  "residential" => "true",
                  "address" => "3700 N Capital of Texas Hwy",
                  "address2" => "Ste 550",
                  "province" => "",
                  "state" => "TX",
                  "city" => "Austin",
                  "postal_code" => "78746",
                  "postal_code_plus_4" => "0036",
                  "country" => "US",
                  "shipping_method" => "Ground",
                  "items_total" => "1",
                  "line_items" => array(
                      array(
                          "item_name" => "Pencil Holder",
                          "sku" => "9876543",
                          "bin_picking_number" => "7",
                          "unit_price" => "1.30",
                          "total_excluding_tax" => "1.30",
                          "price_excluding_tax" => "1.30",
                          "weight_in_ounces" => "10",
                          "product_options" => array(
                              "pa_size" => "large",
                              "Colour" => "Blue"
                          ),
                          "quantity" => "1"
                      )
                  )
              )
          )
	));
	$order = $shippingEasyOrder->create();
	return  response()->json([
		'message'   => $order
	]);

});

Route::get('/ship-order', [
	'as'=>'api.cron.data.ship-order',
	'uses'=> 'Api\V1\CronJobs\OrderController@order',
]);
