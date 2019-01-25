<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddOsAndImeiColumnsToOrderGroupTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('order_group', function (Blueprint $table) {
            $table->text('operating_system')->after('require_device')->nullable();
            $table->string('imei_number', 16)->after('operating_system')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('order_group', function (Blueprint $table) {
            $table->dropColumn([
                'operating_system',
                'imei_number',
            ]);
        });
    }
}
