<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateEmailLogTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('email_log', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('company_id')->nullable();
            $table->integer('customer_id')->nullable();
            $table->integer('staff_id')->nullable();
            $table->integer('business_verficiation_id')->nullable();
            $table->tinyInteger('type')->nullable();
            $table->text('from');
            $table->text('to');
            $table->text('subject');
            $table->text('body');
            $table->text('notes')->nullable();
            $table->text('reply_to')->nullable();
            $table->text('cc')->nullable();
            $table->text('bcc')->nullable();
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
        Schema::dropIfExists('email_log');
    }
}
