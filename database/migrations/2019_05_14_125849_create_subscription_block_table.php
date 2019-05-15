<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSubscriptionBlockTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('subscription_block', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('carrier_block_id');
            $table->foreign('carrier_block_id')->references('id')->on('carrier_block');
            $table->unsignedInteger('subscription_id');
            $table->foreign('subscription_id')->references('id')->on('subscription');
            $table->tinyInteger('is_on');
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
        Schema::dropIfExists('subscription_block');
    }
}
