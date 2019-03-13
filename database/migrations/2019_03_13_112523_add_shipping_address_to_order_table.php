<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddShippingAddressToOrderTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('order', function (Blueprint $table) {
            $table->text('shipping_address1')->after('customer_id')->nullable();
            $table->text('shipping_address2')->after('shipping_address1')->nullable();
            $table->string('shipping_state_id')->after('shipping_address2')->nullable();
            $table->integer('shipping_zip')->after('shipping_state_id')->nullable();
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
                'shipping_address1','shipping_address2','shipping_state_id','shipping_zip'
            ]);
        });
    }
}
