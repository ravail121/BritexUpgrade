<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddProcessedInStandAloneDeviceAndStandAloneSimTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('customer_standalone_device', function (Blueprint $table) {
            $table->boolean('processed')->after('order_id')->nullable();
        });
        Schema::table('customer_standalone_sim', function (Blueprint $table) {
            $table->boolean('processed')->after('order_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('customer_standalone_device', function (Blueprint $table) {
            $table->dropColumn([
                'processed'
            ]);
        });
        Schema::table('customer_standalone_sim', function (Blueprint $table) {
            $table->dropColumn([
                'processed'
            ]);
        });
    }
}
