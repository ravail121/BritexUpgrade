<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddAccountPinPortingFromInPortTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('port', function (Blueprint $table) {
            $table->text('account_pin_porting_from')->after('account_number_porting_from')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('port', function (Blueprint $table) {
            $table->dropColumn([
                'account_pin_porting_from' ]);
        });
    }
}
