<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddOrderNumToSubcriptionStandaloneDeviceSim extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('subscription', function (Blueprint $table) {
            $table->unsignedInteger('order_num')->after('order_id')->nullable();
        });
        Schema::table('customer_standalone_device', function (Blueprint $table) {
            $table->unsignedInteger('order_num')->after('order_id')->nullable();
        });
        Schema::table('customer_standalone_sim', function (Blueprint $table) {
            $table->unsignedInteger('order_num')->after('order_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('subscription', function (Blueprint $table) {
            $table->dropColumn([
                'order_num'
            ]);
        });
        Schema::table('customer_standalone_device', function (Blueprint $table) {
            $table->dropColumn([
                'order_num'
            ]);
        });
        Schema::table('customer_standalone_sim', function (Blueprint $table) {
            $table->dropColumn([
                'order_num'
            ]);
        });
    }
}
