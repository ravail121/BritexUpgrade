<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddPlanProratedAmtToOrderGroupTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('order_group', function (Blueprint $table) {
            $table->decimal('plan_prorated_amt', 6, 2)->after('plan_id')->nullable();
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
                'plan_prorated_amt',
            ]);
        });
    }
}
