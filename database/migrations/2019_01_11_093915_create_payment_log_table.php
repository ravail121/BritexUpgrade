<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePaymentLogTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('payment_log', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('customer_id')->nullable();
            $table->unsignedInteger('order_id')->nullable();
            $table->unsignedInteger('invoice_id')->default(0);
            $table->integer('transaction_num');
            $table->bigInteger('processor_customer_num');
            $table->tinyInteger('status');
            $table->text('error');
            $table->integer('exp')->nullable();
            $table->integer('last4')->nullable();
            $table->text('card_type')->nullable();
            $table->decimal('amount', 6, 2);
            $table->string('card_token')->nullable();
            $table->timestamps();

            $table->foreign('customer_id')
                ->references('id')->on('customer')
                ->onUpdate('cascade')
                ->onDelete('cascade');

            $table->foreign('order_id')
                ->references('id')->on('order')
                ->onUpdate('cascade')
                ->onDelete('cascade');

            $table->foreign('invoice_id')
                ->references('id')->on('invoice')
                ->onUpdate('cascade')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('payment_log');
    }
}
