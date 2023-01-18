<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateOrderTable extends Migration
{
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('company', function (Blueprint $table) {
			$table->increments('id');
			$table->char('api_key');
			$table->char('name');
			$table->string('url');
			$table->tinyInteger('selling_devices')->default('0');
			$table->tinyInteger('selling_plans')->default('0');
			$table->tinyInteger('selling_addons')->default('0');
			$table->tinyInteger('selling_sim_standalone')->default('0');
			$table->tinyInteger('business_verification')->default('0');
			$table->char('regulatory_label')->default('Regulatory');
			$table->double('default_reg_fee')->nullable();
			$table->char('sprint_api_key')->nullable();

			$table->timestamps();
		});



		Schema::create('addon', function (Blueprint $table) {
			$table->increments('id');
			$table->unsignedInteger('company_id')->nullable();
			$table->foreign('company_id')->references('id')->on('company');
			$table->char('name');
			$table->longText('description');
			$table->longText('notes')->nullable();
			$table->longText('image')->nullable();
			$table->double('amount_recurring');
			$table->tinyinteger('taxable');
			$table->tinyinteger('show');
			$table->text('sku')->nullable();
			$table->text('soc_code')->nullable();
			$table->text('bot_code')->nullable();
			$table->timestamps();
		});





		Schema::create('ban', function (Blueprint $table) {
			$table->increments('id');
			$table->text('name');
			$table->integer('number');
			$table->date('billing_day');
			$table->timestamps();
		});


		Schema::create('staff', function (Blueprint $table){

			$table->increments('id');
			$table->unsignedInteger('company_id')->nullable();
			$table->foreign('company_id')->references('id')->on('company');
			$table->integer('level');
			$table->text('fname');
			$table->text('lname');
			$table->char('email');
			$table->char('password');
			$table->char('reset_hash');
			$table->char('phone');
			$table->timestamps();
		});


		Schema::create('ban_note', function(Blueprint $table){

			$table->increments('id');
			$table->unsignedInteger('staff_id')->nullable();
			$table->foreign('staff_id')->references('id')->on('staff');
			$table->date('date');
			$table->text('text');
			$table->timestamps();

		});

		Schema::create('ban_group', function(Blueprint $table){

			$table->increments('id');
			$table->unsignedInteger('ban_id')->nullable();
			$table->foreign('ban_id')->references('id')->on('ban');
			$table->char('number');
			$table->timestamps();
		});



		Schema::create('tax', function(Blueprint $table){

			$table->increments('id');
			$table->unsignedInteger('company_id')->nullable();
			$table->foreign('company_id')->references('id')->on('company');
			$table->text('state');
			$table->double('rate');
			$table->timestamps();
		});

		Schema::create('business_verification',function(Blueprint $table){
			$table->increments('id');
			$table->tinyinteger('approved');
			$table->char('hash', 60);
			$table->text('business_name');
			$table->char('tax_id')->nullable();
			$table->text('fname');
			$table->text('lname');
			$table->text('email');
			// $table->text('address_line1')->nullable();
			// $table->text('address_line2')->nullable();
			// $table->text('city')->nullable();
			// $table->text('state')->nullable();
			// $table->text('zip')->nullable();
			$table->timestamps();


		});


		Schema::create('business_verification_doc',function(Blueprint $table){
			$table->increments('id');
			$table->unsignedInteger('bus_ver_id')->nullable();
			$table->foreign('bus_ver_id')->references('id')->on('business_verification');
			$table->text('src');
			$table->timestamps();

		});

		Schema::create('carrier',function(Blueprint $table){
			$table->increments('id');
			$table->text('name');
			$table->timestamps();

		});

		Schema::create('carrier_block',function(Blueprint $table){
			$table->increments('id');
			$table->unsignedInteger('carrier_id')->nullable();
			$table->foreign('carrier_id')->references('id')->on('carrier');
			$table->integer('type');
			$table->text('display_name');
			$table->text('bot_code');
			$table->text('soc_code');
			$table->timestamps();

		});

		Schema::create('company_to_carrier', function(Blueprint $table){

			$table->increments('id');
			$table->unsignedInteger('company_id')->nullable();
			$table->foreign('company_id')->references('id')->on('company');
			$table->unsignedInteger('carrier_id')->nullable();
			$table->foreign('carrier_id')->references('id')->on('carrier');
			$table->timestamps();

		});

		Schema::create('coupon',function(Blueprint $table){

			$table->increments('id');
			$table->unsignedInteger('company_id')->nullable();
			$table->foreign('company_id')->references('id')->on('company');
			$table->tinyinteger('active');
			$table->tinyinteger('class');
			$table->tinyinteger('fixed_or_perc');
			$table->double('amount');
			$table->text('name');
			$table->text('code');
			$table->integer('num_cycles');
			$table->integer('max_uses');
			$table->integer('num_uses');
			$table->tinyinteger('stackable');
			$table->datetime('start_date')->nullable();
			$table->datetime('end_date')->nullable();
			$table->integer('multiline_min')->nullable();
			$table->integer('multiline_max')->nullable();
			$table->tinyinteger('multiline_restrict_plans')->nullable();
			$table->timestamps();

		});


		Schema::create('coupon_multiline_plan_type',function(Blueprint $table){

			$table->increments('id');
			$table->unsignedInteger('coupon_id')->nullable();
			$table->foreign('coupon_id')->references('id')->on('coupon');
			$table->tinyinteger('plan_type');
			$table->timestamps();


		});

		Schema::create('coupon_product_type',function(Blueprint $table){

			$table->increments('id');
			$table->unsignedInteger('coupon_id')->nullable();
			$table->foreign('coupon_id')->references('id')->on('coupon');
			$table->double('amount');
			$table->tinyinteger('type');
			$table->tinyinteger('sub_type');
			$table->timestamps();


		});


		Schema::create('customer', function(Blueprint $table){

			$table->increments('id');
			$table->char('hash',60);
			$table->unsignedInteger('company_id')->nullable();
			$table->foreign('company_id')->references('id')->on('company');
			$table->unsignedInteger('business_verification_id')->nullable();
			$table->foreign('business_verification_id')->references('id')->on('business_verification');
			$table->date('subscription_start_date')->nullable();
			$table->date('billing_start')->nullable();
			$table->date('billing_end')->nullable();
			$table->integer('primary_payment_method')->nullable();
			$table->integer('primary_payment_card')->nullable();
			$table->tinyinteger('account_suspended')->default(0);
			$table->text('billing_address1')->nullable();
			$table->text('billing_address2')->nullable();
			$table->text('billing_city')->nullable();
			$table->string('billing_zip')->nullable();
			$table->char('billing_state_id', 2)->nullable();
			$table->text('shipping_address1');
			$table->text('shipping_address2')->nullable();
			$table->text('shipping_city');
			$table->char('shipping_state_id', 2);
			$table->timestamps();

		});

		Schema::create('customer_note',function(Blueprint $table){

			$table->increments('id');
			$table->unsignedInteger('customer_id')->nullable();
			$table->foreign('customer_id')->references('id')->on('customer');
			$table->unsignedInteger('staff_id')->nullable();
			$table->foreign('staff_id')->references('id')->on('staff');
			$table->date('date');
			$table->text('text');
			$table->timestamps();
		});

		Schema::create('default_imei',function(Blueprint $table){
			$table->increments('id');
			$table->integer('sort')->default(0);
			$table->integer('type');
			$table->char('os');
			$table->text('code');
			$table->timestamps();

		});

		Schema::create('tag',function(Blueprint $table){
			$table->increments('id');
			$table->unsignedInteger('company_id')->nullable();
			$table->foreign('company_id')->references('id')->on('company');
			$table->text('name');
			$table->timestamps();
		});


		Schema::create('device',function(Blueprint $table){

			$table->increments('id');
			$table->unsignedInteger('company_id')->nullable();
			$table->foreign('company_id')->references('id')->on('company');
			$table->unsignedInteger('carrier_id')->nullable();
			$table->foreign('carrier_id')->references('id')->on('carrier');
			$table->integer('type');
			$table->text('name');
			$table->text('description')->nullable();
			$table->text('description_detail')->nullable();
			$table->unsignedInteger('tag_id')->nullable();
			$table->foreign('tag_id')->references('id')->on('tag');
			$table->text('notes')->nullable();
			$table->text('primary_image')->nullable();
			$table->double('amount');
			$table->double('amount_w_plan');
			$table->tinyinteger('taxable');
			$table->tinyinteger('associate_with_plan')->nullable();
			$table->tinyinteger('show');
			$table->text('sku')->nullable();
			$table->text('os')->nullable();
			$table->timestamps();

		});

		Schema::create('device_group',function(Blueprint $table){

			$table->increments('id');
			$table->unsignedInteger('company_id')->nullable();
			$table->foreign('company_id')->references('id')->on('company');
			$table->char('name');
			$table->timestamps();

		});

		Schema::create('device_to_group',function(Blueprint $table){

			$table->increments('id');
			$table->unsignedInteger('device_id')->nullable();
			$table->unsignedInteger('device_group_id')->nullable();
			$table->foreign('device_group_id')->references('id')->on('device_group');
			$table->foreign('device_id')->references('id')->on('device');
			$table->timestamps();

		});


		Schema::create('device_image',function(Blueprint $table){
			$table->increments('id');
			$table->unsignedInteger('device_id')->nullable();
			$table->foreign('device_id')->references('id')->on('device');
			$table ->text('source');
			$table->timestamps();
		});


		//  Schema::create('device_to_carrier',function(Blueprint $table){

		//  $table->increments('id');
		//  $table->unsignedInteger('device_group_id');
		//  $table->foreign('device_group_id')->references('id')->on('device_groups');
		//  $table->unsignedInteger('device_id');
		//  $table->foreign('device_id')->references('id')->on('device');
		//  $table->timestamps();

		// });


		Schema::create('device_to_carrier',function(Blueprint $table){

			$table->increments('id');
			$table->unsignedInteger('device_id')->nullable();
			$table->unsignedInteger('carrier_id')->nullable();
			$table->foreign('device_id')->references('id')->on('device');
			$table->foreign('carrier_id')->references('id')->on('carrier');
			$table->timestamps();

		});

		Schema::create('plan',function(Blueprint $table){

			$table->increments('id');
			$table->unsignedInteger('device_id')->nullable();
			$table->unsignedInteger('carrier_id')->nullable();
			$table->foreign('device_id')->references('id')->on('device');
			$table->foreign('carrier_id')->references('id')->on('carrier');
			$table->integer('type');
			$table->unsignedInteger('tag_id')->nullable();
			$table->foreign('tag_id')->references('id')->on('tag');
			$table->text('name');
			$table->text('image')->nullable();
			$table->text('description')->nullable();
			$table->text('notes')->nullable();
			$table->text('primary_image')->nullable();
			$table->double('amount_recurring');
			$table->double('amount_onetime');
			$table->tinyinteger('regulatory_fee_type')->nullable();
			$table->double('regulatory_fee_amount');
			$table->tinyinteger('sim_required');
			$table->tinyinteger('taxable');
			$table->tinyinteger('show');
			$table->text('sku')->nullable();
			$table->integer('data_limit')->nullable();
			$table->text('rate_plan_soc')->nullable();
			$table->text('rate_plan_bot_code')->nullable();
			$table->text('data_soc')->nullable();
			$table->tinyinteger('signup_porting');
			$table->tinyinteger('subsequent_porting');
			$table->tinyinteger('area_code');
			$table->tinyinteger('imei_required');
			$table->tinyinteger('associate_with_device');
			$table->tinyinteger('affilate_credit')->default('1');
			$table->timestamps();
		});

		Schema::create('device_to_plan',function(Blueprint $table){

			$table->increments('id');
			$table->unsignedInteger('device_id')->nullable();
			$table->unsignedInteger('plan_id')->nullable();
			$table->foreign('device_id')->references('id')->on('device');
			$table->foreign('plan_id')->references('id')->on('plan');
			$table->timestamps();

		});

		Schema::create('sim',function(Blueprint $table){

			$table->increments('id');
			$table->unsignedInteger('company_id')->nullable();
			$table->unsignedInteger('carrier_id')->nullable();
			$table->foreign('company_id')->references('id')->on('company');
			$table->foreign('carrier_id')->references('id')->on('carrier');
			$table->text('name');
			$table->text('description');
			$table->text('notes')->nullable();;
			$table->text('image');
			$table->double('amount_alone');
			$table->double('amount_w_plan');
			$table->tinyinteger('taxable');
			$table->tinyinteger('show');
			$table->text('sku');
			$table->text('code');
			$table->timestamps();

		});

		Schema::create('device_to_sim',function(Blueprint $table){

			$table->increments('id');
			$table->unsignedInteger('device_id')->nullable();
			$table->unsignedInteger('sim_id')->nullable();
			$table->foreign('device_id')->references('id')->on('device');
			$table->foreign('sim_id')->references('id')->on('sim');
			$table->timestamps();

		});


		Schema::create('email_template', function(Blueprint $table){
			$table->increments('id');
			$table->unsignedInteger('company_id')->nullable();
			$table->foreign('company_id')->references('id')->on('company');
			$table->char('code');
			$table->text('from');
			$table->text('to');
			$table->text('subject');
			$table->text('body');
			$table->text('notes')->nullable();
			$table->text('reply_to')->nullable();
			$table->text('cc')->nullable();
			$table->text('bcc')->nullable();
			$table->timestamps();

		});

		Schema::create('invoice',function(Blueprint $table){

			$table->increments('id');
			$table->unsignedInteger('customer_id')->nullable();
			$table->foreign('customer_id')->references('id')->on('customer');
			$table->tinyinteger('type');
			$table->integer('status');
			$table->date('start_date');
			$table->date('end_date');
			$table->date('due_date');
			$table->double('subtotal');
			$table->double('total_due');
			$table->double('prev_balance');
			$table->text('payment_method');
			$table->text('notes');
			$table->text('business_name')->nullable();
			$table->text('billing_fname');
			$table->text('billing_lname');
			$table->text('billing_address_line_1');
			$table->text('billing_address_line_2')->nullable();
			$table->text('billing_city');
			$table->text('billing_state');
			$table->text('billing_zip');
			$table->text('shipping_fname');
			$table->text('shipping_lname');
			$table->text('shipping_address_line_1');
			$table->text('shipping_address_line_2')->nullable();
			$table->text('shipping_city');
			$table->text('shipping_state');
			$table->text('shipping_zip');
			$table->timestamps();

		});

		Schema::create('subscription',function(Blueprint $table){

			$table->increments('id');
			$table->unsignedInteger('order_id')->nullable();
			$table->unsignedInteger('customer_id')->nullable();
			$table->unsignedInteger('plan_id')->nullable();
			$table->string('phone_number')->nullable();
			$table->char('status')->nullable();
			$table->char('suspend_restore_status')->nullable();
			$table->char('upgrade_downgrade_status')->nullable();
			$table->date('upgrade_downgrade_date_submitted');
			$table->tinyInteger('port_in_progress')->default(0);
			$table->text('sim_name')->nullable();
			$table->text('sim_card_num');
			$table->integer('old_plan_id')->nullable();
			$table->integer('new_plan_id')->nullable();
			$table->date('downgrade_date')->nullable();
			$table->string('tracking_num')->nullable();
			$table->unsignedInteger('device_id')->nullable();
			$table->text('device_os')->nullable();
			$table->string('device_imei', 16);
			$table->text('subsequent_porting');
			$table->string('requested_area_code')->nullable();
			$table->unsignedInteger('ban_id')->nullable();
			$table->unsignedInteger('ban_group_id')->nullable();
			$table->date('activation_date')->nullable();
			$table->date('suspended_date')->nullable();
			$table->date('closed_date')->nullable();

			$table->foreign('order_id')
			      ->references('id')->on('order')
			      ->onUpdate('cascade')
			      ->onDelete('cascade');

			$table->foreign('customer_id')
			      ->references('id')->on('customer')
			      ->onUpdate('cascade')
			      ->onDelete('cascade');

			$table->foreign('plan_id')
			      ->references('id')->on('plan')
			      ->onUpdate('cascade')
			      ->onDelete('cascade');

			$table->foreign('device_id')
			      ->references('id')->on('device')
			      ->onUpdate('cascade')
			      ->onDelete('cascade');

			$table->foreign('ban_id')
			      ->references('id')->on('ban')
			      ->onUpdate('cascade')
			      ->onDelete('cascade');

			$table->foreign('ban_group_id')
			      ->references('id')->on('ban_group')
			      ->onUpdate('cascade')
			      ->onDelete('cascade');

		});

		Schema::create('invoice_item', function(Blueprint $table){
			$table->increments('id');
			$table->unsignedInteger('invoice_id')->nullable();
			$table->unsignedInteger('subscription_id')->nullable();
			$table->foreign('invoice_id')->references('id')->on('invoice');
			$table->foreign('subscription_id')->references('id')->on('subscription');
			$table->text('product_type');
			$table->integer('product_id')->nullable();
			$table->integer('type');
			$table->date('start_date')->nullable();
			$table->text('description');
			$table->double('amount');
			$table->tinyinteger('taxable');

		});

		Schema::create('order', function(Blueprint $table){
			$table->increments('id');
			$table->integer('active_group_id')->nullable();
			$table->integer('active_subscription_id')->nullable();
			$table->integer('order_num');
			$table->tinyinteger('status');
			$table->unsignedInteger('invoice_id')->nullable();
			$table->foreign('invoice_id')->references('id')->on('invoice');
			$table->char('hash', 60);
			$table->unsignedInteger('company_id')->nullable();
			$table->unsignedInteger('customer_id')->nullable();
			$table->foreign('company_id')->references('id')->on('company');
			$table->foreign('customer_id')->references('id')->on('customer');
			$table->date('date_processed');
			$table->timestamps();

		});

		Schema::create('order_plan', function(Blueprint $table){
			$table->increments('id');
			$table->unsignedInteger('order_id')->nullable();
			$table->unsignedInteger('plan_id')->nullable();
			$table->foreign('order_id')->references('id')->on('order');
			$table->foreign('plan_id')->references('id')->on('plan');
			$table->timestamps();

		});
		Schema::create('order_sim', function(Blueprint $table){
			$table->increments('id')->nullable();
			$table->unsignedInteger('order_id')->nullable();
			$table->unsignedInteger('sim_id')->nullable();
			$table->foreign('order_id')->references('id')->on('order');
			$table->tinyinteger('order_plan_id');
			$table->foreign('sim_id')->references('id')->on('sim');
			$table->timestamps();
		});

		Schema::create('order_coupon', function(Blueprint $table){
			$table->increments('id');
			$table->unsignedInteger('order_id')->nullable();
			$table->unsignedInteger('coupon_id')->nullable();
			$table->foreign('order_id')->references('id')->on('order');
			$table->foreign('coupon_id')->references('id')->on('coupon');
			$table->timestamps();

		});

		Schema::create('order_group', function(Blueprint $table){
			$table->increments('id');
			$table->unsignedInteger('order_id')->nullable();
			$table->foreign('order_id')->references('id')->on('order');
			$table->tinyinteger('closed');
			$table->unsignedInteger('device_id')->nullable();
			$table->foreign('device_id')->references('id')->on('device')->default('0');
			$table->unsignedInteger('plan_id')->nullable();
			$table->foreign('plan_id')->references('id')->on('plan')->default('0');
			$table->unsignedInteger('sim_id')->nullable();
			$table->foreign('sim_id')->references('id')->on('sim')->default('0');
			$table->string('sim_num');
			$table->text('sim_type');
			$table->text('porting_number');
			$table->text('area_code');
			$table->tinyinteger('require_plan');
			$table->tinyinteger('require_device');
			$table->timestamps();



		});

		Schema::create('order_group_addon', function(Blueprint $table){
			$table->increments('id');
			$table->unsignedInteger('order_group_id')->nullable();
			$table->foreign('order_group_id')->references('id')->on('order_group');
			$table->unsignedInteger('addon_id')->nullable();
			$table->foreign('addon_id')->references('id')->on('addon');
			$table->unsignedInteger('subscription_id')->nullable();
			$table->foreign('subscription_id')->references('id')->on('subscription');
			$table->timestamps();
		});

		Schema::create('pending_charge',function(Blueprint $table){
			$table->increments('id');
			$table->unsignedInteger('customer_id')->nullable();
			$table->unsignedInteger('invoice_id')->nullable();
			$table->foreign('customer_id')->references('id')->on('customer');
			$table->foreign('invoice_id')->references('id')->on('invoice')->default('0');
			$table->tinyinteger('type');
			$table->double('amount');
			$table->text('description');
			$table->timestamps();

		});

		Schema::create('plan_block',function(Blueprint $table){
			$table->increments('id');
			$table->unsignedInteger('plan_id')->nullable();
			$table->unsignedInteger('carrier_block_id')->nullable();
			$table->foreign('plan_id')->references('id')->on('plan');
			$table->foreign('carrier_block_id')->references('id')->on('carrier_block');
			$table->timestamps();
		});

		Schema::create('plan_custom_type',function(Blueprint $table){
			$table->increments('id');
			$table->unsignedInteger('company_id')->nullable();
			$table->unsignedInteger('plan_id')->nullable();
			$table->foreign('company_id')->references('id')->on('company');
			$table->foreign('plan_id')->references('id')->on('plan');
			$table->text('name');
			$table->timestamps();
		});

		Schema::create('plan_data_soc_bot_code',function(Blueprint $table){
			$table->increments('id');
			$table->unsignedInteger('plan_id')->nullable();
			$table->foreign('plan_id')->references('id')->on('plan');
			$table->text('data_soc_bot_code');
			$table->timestamps();
		});



		Schema::create('plan_to_addon',function(Blueprint $table){
			$table->increments('id');
			$table->unsignedInteger('plan_id')->nullable();
			$table->unsignedInteger('addon_id')->nullable();
			$table->foreign('plan_id')->references('id')->on('plan');
			$table->foreign('addon_id')->references('id')->on('addon');
			$table->timestamps();
		});



		Schema::create('port',function(Blueprint $table){
			$table->increments('id');
			$table->unsignedInteger('subscription_id')->nullable();
			$table->foreign('subscription_id')->references('id')->on('subscription');
			$table->tinyinteger('status');
			$table->text('notes');
			$table->text('number_to_port');
			$table->text('company_porting_from');
			$table->text('account_number_porting_from');
			$table->text('authorized_name');
			$table->text('address_line1');
			$table->text('address_line2');
			$table->text('city');
			$table->text('state');
			$table->text('zip');
			$table->text('ssn_taxid')->nullable();
			$table->timestamps();

		});


		Schema::create('port_note',function(Blueprint $table){
			$table->increments('id');
			$table->unsignedInteger('port_id')->nullable();
			$table->unsignedInteger('staff_id')->nullable();
			$table->foreign('port_id')->references('id')->on('port');
			$table->foreign('staff_id')->references('id')->on('staff');
			$table->date('date');
			$table->text('text');
			$table->timestamps();
		});

		Schema::create('subscription_addon',function(Blueprint $table){

			$table->increments('id');
			$table->unsignedInteger('subscription_id')->nullable();
			$table->unsignedInteger('addon_id')->nullable();
			$table->foreign('subscription_id')->references('id')->on('subscription');
			$table->foreign('addon_id')->references('id')->on('addon');
			$table->char('status');
			$table->date('removal_date')->nullable();
			$table->timestamps();

		});


		Schema::create('subscription_coupon',function(Blueprint $table){

			$table->increments('id');
			$table->unsignedInteger('subscription_id')->nullable();
			$table->unsignedInteger('coupon_id')->nullable();
			$table->foreign('subscription_id')->references('id')->on('subscription');
			$table->foreign('coupon_id')->references('id')->on('coupon');
			$table->integer('cycles_remaining');
			$table->timestamps();

		});


		Schema::create('subscription_log',function(Blueprint $table){

			$table->increments('id');
			$table->unsignedInteger('company_id')->nullable();
			$table->unsignedInteger('customer_id')->nullable();
			$table->unsignedInteger('subscription_id')->nullable();
			$table->foreign('company_id')->references('id')->on('company');
			$table->foreign('customer_id')->references('id')->on('customer');
			$table->foreign('subscription_id')->references('id')->on('subscription');
			$table->date('date');
			$table->char('category');
			$table->integer('product_id');
			$table->text('description');
			$table->integer('old_product');
			$table->integer('new_product');
			$table->timestamps();

		});

		Schema::create('system_global_setting',function(Blueprint $table){

			$table->increments('id');
			$table->text('site_url');
			$table->text('upload_path');
			$table->timestamps();
		});


		Schema::create('system_email_template',function(Blueprint $table){

			$table->increments('id');
			$table->text('code');
			$table->text('name');
			$table->text('description');
			$table->timestamps();
		});

		Schema::create('system_email_template_dynamic_field', function(Blueprint $table){
			$table->increments('id');
			$table->text('code');
			$table->text('name');
			$table->text('description');
		});



	}



	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::dropIfExists('company');
		Schema::dropIfExists('addon');
		Schema::dropIfExists('ban');
		Schema::dropIfExists('staff');
		Schema::dropIfExists('ban_note');
		Schema::dropIfExists('ban_group');
		Schema::dropIfExists('tax');
		Schema::dropIfExists('business_verification');
		Schema::dropIfExists('business_verification_doc');
		Schema::dropIfExists('carrier');
		Schema::dropIfExists('carrier_block');
		Schema::dropIfExists('company_to_carrier');
		Schema::dropIfExists('coupon');
		Schema::dropIfExists('coupon_multiline_plan_type');
		Schema::dropIfExists('coupon_product_type');
		Schema::dropIfExists('customer');
		Schema::dropIfExists('customer_note');
		Schema::dropIfExists('default_imei');
		Schema::dropIfExists('tag');
		Schema::dropIfExists('device');
		Schema::dropIfExists('device_group');
		Schema::dropIfExists('device_to_group');
		Schema::dropIfExists('device_image');
		Schema::dropIfExists('device_to_carrier');
		Schema::dropIfExists('plan');
		Schema::dropIfExists('device_to_plan');
		Schema::dropIfExists('sim');
		Schema::dropIfExists('device_to_sim');
		Schema::dropIfExists('email_template');
		Schema::dropIfExists('invoice');
		Schema::dropIfExists('subscription');
		Schema::dropIfExists('invoice_item');
		Schema::dropIfExists('order');
		Schema::dropIfExists('order_plan');
		Schema::dropIfExists('order_sim');
		Schema::dropIfExists('order_coupon');
		Schema::dropIfExists('order_group');
		Schema::dropIfExists('order_group_addon');
		Schema::dropIfExists('pending_charge');
		Schema::dropIfExists('plan_block');
		Schema::dropIfExists('plan_custom_type');
		Schema::dropIfExists('plan_data_soc_bot_code');
		Schema::dropIfExists('plan_to_addon');
		Schema::dropIfExists('port');
		Schema::dropIfExists('port_note');
		Schema::dropIfExists('subscription_addon');
		Schema::dropIfExists('subscription_coupon');
		Schema::dropIfExists('subscription_log');
		Schema::dropIfExists('system_global_setting');
		Schema::dropIfExists('system_email_template');
		Schema::dropIfExists('system_email_template_dynamic_field');
	}
}
