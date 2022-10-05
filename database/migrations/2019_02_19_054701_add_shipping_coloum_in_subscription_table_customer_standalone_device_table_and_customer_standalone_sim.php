<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddShippingColoumInSubscriptionTableCustomerStandaloneDeviceTableAndCustomerStandaloneSim extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('subscription', function($table) {
            $table->date('shipping_date')->after('requested_area_code')->nullable();
        });
        Schema::table('customer_standalone_device', function($table) {
            $table->date('shipping_date')->after('imei')->nullable();
        });
        Schema::table('customer_standalone_sim', function($table) {
            $table->date('shipping_date')->after('sim_num')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('subscription', function($table) {
            $table->dropColumn('shipping_date');
        });
        Schema::table('customer_standalone_device', function($table) {
            $table->dropColumn('shipping_date');
        });
        Schema::table('customer_standalone_sim', function($table) {
            $table->dropColumn('shipping_date');
        });
    }
}
