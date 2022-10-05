<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateRefundLogTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('payment_refund_log', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('payment_log_id');
            $table->foreign('payment_log_id')->references('id')->on('payment_log');
            $table->bigInteger('transaction_num')->nullable();
            $table->string('error')->nullable();
            $table->double('amount');
            $table->tinyinteger('status');
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
        Schema::dropIfExists('payment_refund_log');
    }
}
