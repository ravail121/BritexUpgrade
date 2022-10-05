<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddShippingFnameAndShippingLnameToOrderTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
     public function up()
    {
        Schema::table('order', function (Blueprint $table) {
            $table->text('shipping_fname')->after('date_processed')->nullable();
            $table->text('shipping_lname')->after('shipping_fname')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('order', function (Blueprint $table) {
            $table->dropColumn([
                'shipping_fname','shipping_lname'
            ]);
        });
    }
}
