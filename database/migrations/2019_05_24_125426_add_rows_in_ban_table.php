<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddRowsInBanTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('ban', function (Blueprint $table) {
            $table->unsignedInteger('company_id')->after('id')->nullable();
            $table->foreign('company_id')->references('id')->on('company');
            $table->unsignedInteger('fan_id')->after('company_id')->nullable();
            $table->foreign('fan_id')->references('id')->on('fan');
            $table->unsignedInteger('node_id')->after('fan_id')->nullable();
            $table->foreign('node_id')->references('id')->on('node');
            $table->integer('voice_limit')->after('billing_start_day')->nullable();
            $table->integer('data_limit')->after('voice_limit')->nullable();
            $table->integer('total_limit')->after('data_limit')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('ban', function (Blueprint $table) {
            $table->dropColumn([
                'order_id', 'staff_id'
            ]);
        });
    }
}
