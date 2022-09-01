<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterOldProductAndNewProductInSubscriptionLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
	    Schema::table('subscription_log', function (Blueprint $table) {
		    $table->text('old_product')->nullable()->change();
		    $table->text('new_product')->nullable()->change();
	    });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
	    Schema::table('subscription_log', function (Blueprint $table) {
		    $table->integer( 'old_product' )->nullable()->change();
		    $table->integer( 'new_product' )->nullable()->change();
	    });
    }
}
