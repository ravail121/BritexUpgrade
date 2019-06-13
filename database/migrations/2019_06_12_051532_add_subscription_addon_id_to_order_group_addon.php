<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddSubscriptionAddonIdToOrderGroupAddon extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('order_group_addon', function (Blueprint $table) {
            $table->Integer('subscription_addon_id')->after('subscription_id')->nullable();
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
                'subscription_addon_id',
            ]);
        });
    }
}
