<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCustomerCreditCardsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('customer_credit_cards', function (Blueprint $table) {
            $table->increments('id');
            $table->string('api_key');
            $table->unsignedInteger('customer_id');
            $table->string('cardholder');
            $table->string('number');
            $table->integer('expiration');
            $table->integer('cvc');
            $table->text('billing_address1');
            $table->integer('billing_zip');
            $table->timestamps();

            $table->foreign('customer_id')
                ->references('id')->on('customer')
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
        Schema::dropIfExists('customer_credit_cards');
    }
}
