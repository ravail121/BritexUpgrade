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
            $table->tinyInteger('selling_plans');
            $table->tinyInteger('selling_addons');
            $table->text('selling_sim_standalone');
            $table->tinyInteger('business_verification')->default('0');
            $table->char('regulatory_label')->default('Regulatory');
            $table->double('default_reg_fee');
            $table->char('sprint_api_key');

            $table->timestamps();
        });



         Schema::create('addon', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('company_id')->nullable();
            $table->foreign('company_id')->references('id')->on('company');
            $table->char('name');
            $table->longText('description');
            $table->longText('notes');
            $table->longText('image');
            $table->double('amount_recurring');
            $table->tinyinteger('taxable');
            $table->tinyinteger('show');
            $table->text('sku');
            $table->text('soc_code');
            $table->text('bot_code');
            $table->timestamps();
        });





         Schema::create('ban', function (Blueprint $table) {
            $table->increments('id');
            $table->text('name');
            $table->integer('number');
            $table->integer('billing_day');
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
           $table->char('tax_id');
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
           $table->integer('display_name');
           $table->integer('bot_code');
           $table->integer('soc_code');
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
            $table->datetime('start_date');
            $table->datetime('end_date');
            $table->integer('multiline_min');
            $table->integer('multiline_max');
            $table->tinyinteger('multiline_restrict_plans');
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
            $table->text('description');
            $table->unsignedInteger('tag_id')->nullable();
            $table->foreign('tag_id')->references('id')->on('tag');
            $table->text('notes');
            $table->text('primary_image');
            $table->double('amount');
            $table->double('amount_w_plan');
            $table->tinyinteger('taxable'); 
            $table->tinyinteger('associate_with_plan');
            $table->tinyinteger('show');
            $table->text('sku');
            $table->text('os');
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
            $table->text('image');
            $table->text('description');
            $table->text('notes');
            $table->text('primary_image');
            $table->double('amount_recurring');
            $table->double('amount_onetime');
            $table->tinyinteger('regulatory_fee_type');
            $table->double('regulatory_fee_amount');
            $table->tinyinteger('sim_required');
            $table->tinyinteger('taxable');
            $table->tinyinteger('show');
            $table->text('sku');
            $table->integer('data_limit');
            $table->text('rate_plan_soc');
            $table->text('rate_plan_bot_code');
            $table->text('data_soc');
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
            $table->text('notes');
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
            $table->text('business_name');
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
            $table->char('phone_number');
            $table->char('status')->nullable();      
            $table->char('suspend_restore_status');
            $table->char('upgrade_downgrade_status');
            $table->date('upgrade_downgrade_date_submitted');
            $table->tinyInteger('port_in_progress')->default(0);
            $table->text('sim_name');
            $table->text('sim_card_num');
            $table->integer('old_plan_id'); 
            $table->integer('new_plan_id');
            $table->date('downgrade_date')->nullable();
            $table->integer('tracking_num');
            $table->unsignedInteger('device_id')->nullable();
            $table->text('device_os');
            $table->string('device_imei', 16);
            $table->text('subsequent_porting');
            $table->integer('requested_area_code');
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
           $table->integer('product_id');
           $table->integer('type');
           $table->date('start_date');
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
           $table->bigInteger('sim_num');
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
            $table->text('ssn_taxid');
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
            $table->date('removal_date');
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
        //Schema::dropIfExists('order');
    }
}
