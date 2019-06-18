<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddPaidColumnToOrderGroupAndOrderGroupAddonTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('order_group', function (Blueprint $table) {
            $table->boolean('paid')->after('closed')->nullable();
        });
        Schema::table('order_group_addon', function (Blueprint $table) {
            $table->boolean('paid')->after('addon_id')->nullable();
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
                'paid',
            ]);
        });
        Schema::table('order_group_addon', function (Blueprint $table) {
            $table->dropColumn([
                'paid',
            ]);
        });
    }
}
