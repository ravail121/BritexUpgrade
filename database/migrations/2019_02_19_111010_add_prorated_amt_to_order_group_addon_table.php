<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddProratedAmtToOrderGroupAddonTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('order_group_addon', function (Blueprint $table) {
            $table->decimal('prorated_amt', 6, 2)->after('addon_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('order_group_addon', function (Blueprint $table) {
            $table->dropColumn([
                'prorated_amt',
            ]);
        });
    }
}
