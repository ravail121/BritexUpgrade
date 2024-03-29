<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAttTwoUsageDataTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('att_two_usage_data', function (Blueprint $table) {
            $table->increments('id');
            $table->string('iccid');
            $table->string('carrier');
            $table->string('status');
            $table->string('date_activated')->nullable();
            $table->string('usage_data')->nullable();
	        $table->index('iccid');
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
        Schema::dropIfExists('att_two_usage_data');
    }
}
