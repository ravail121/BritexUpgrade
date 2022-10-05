<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateOrderCouponProductTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        
        Schema::create('order_coupon_product', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('order_coupon_id')->nullable();
            $table->foreign('order_coupon_id')->references('id')->on('order_coupon');
            $table->text('order_product_type');
            $table->integer('order_product_id');
            $table->decimal('amount', 9, 2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('order_coupon_product');
    }
}
