<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCustomerCouponTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('customer_coupon', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('customer_id')->nullable();
            $table->unsignedInteger('coupon_id')->nullable();
            $table->integer('cycles_remaining')->nullable();

            $table->foreign('customer_id')
                  ->references('id')
                  ->on('customer');

            $table->foreign('coupon_id')
                  ->references('id')
                  ->on('coupon');

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
        Schema::dropIfExists('customer_coupon');
    }
}
