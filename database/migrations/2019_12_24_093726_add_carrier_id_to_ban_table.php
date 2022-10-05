<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddCarrierIdToBanTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('ban', function (Blueprint $table) {
            $table->unsignedInteger('carrier_id')->after('id')->nullable();
            $table->foreign('carrier_id')->references('id')->on('carrier')
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
        Schema::table('ban', function (Blueprint $table) {
            $table->dropForeign(['carrier_id']);
            $table->dropColumn([
                'carrier_id'
            ]);
        });
    }
}
