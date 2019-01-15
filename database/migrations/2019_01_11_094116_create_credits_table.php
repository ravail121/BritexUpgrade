<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCreditsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('credits', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('customer_id')->nullable();
            $table->double('amount', 8, 2);
            $table->tinyInteger('applied_to_invoice')->default(0);
            $table->tinyInteger('type')->default(1);
            $table->date('date');
            $table->tinyInteger('payment_method')->default(1);
            $table->string('description');
            $table->tinyInteger('account_level')->default(1);
            $table->tinyInteger('subscription_id')->default(0);
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
        Schema::dropIfExists('credits');
    }
}
