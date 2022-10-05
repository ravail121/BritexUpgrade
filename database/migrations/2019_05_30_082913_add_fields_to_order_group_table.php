<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddFieldsToOrderGroupTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('order_group', function (Blueprint $table) {
            $table->Integer('change_subscription')->after('porting_number')->default(0);
            $table->unsignedInteger('subscription_id')->after('change_subscription')->nullable();
            $table->unsignedInteger('old_subscription_plan_id')->after('subscription_id')->nullable();
            $table->foreign('subscription_id')->references('id')->on('subscription');
            $table->foreign('old_subscription_plan_id')->references('id')->on('plan');
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
                'change_subscription','subscription_id','old_subscription_plan_id'
            ]);
        });
    }
}
