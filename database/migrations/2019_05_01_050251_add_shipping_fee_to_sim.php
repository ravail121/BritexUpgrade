<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddShippingFeeToSim extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('sim', function (Blueprint $table) {
            $table->integer('shipping_fee')->after('code')->nullable();
        });         
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('sim', function (Blueprint $table) {
            $table->dropColumn([
                'shipping_fee'
            ]);
        });         
    }
}
