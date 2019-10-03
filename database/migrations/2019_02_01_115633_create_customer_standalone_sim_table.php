<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCustomerStandaloneSimTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('customer_standalone_sim', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('customer_id')->nullable();
            $table->unsignedInteger('sim_id')->nullable();
            $table->unsignedInteger('order_id')->nullable();
            $table->string('status');
            $table->string('tracking_num')->nullable();
            $table->text('sim_num');
            $table->timestamps();

            $table->foreign('customer_id')
                ->references('id')->on('customer')
                ->onUpdate('cascade')
                ->onDelete('cascade');

            $table->foreign('sim_id')
                ->references('id')->on('sim')
                ->onUpdate('cascade')
                ->onDelete('cascade');

            $table->foreign('order_id')
                ->references('id')->on('order')
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
        Schema::dropIfExists('customer_standalone_sim');
    }
}
