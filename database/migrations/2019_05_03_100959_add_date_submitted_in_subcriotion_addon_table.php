<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddDateSubmittedInSubcriotionAddonTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('subscription_addon', function (Blueprint $table) {
            $table->date('date_submitted')->after('removal_date')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('subscription_addon', function (Blueprint $table) {
            $table->dropColumn([
                'date_submitted'
            ]);
        });
    }
}
