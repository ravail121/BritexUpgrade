<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddOrderNumInSubscriptionLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
	    Schema::table('subscription_log', function(Blueprint $table){
		    $table->integer('order_num')->after('new_product')->nullable();
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
		    $table->dropColumn([
			    'order_num'
		    ]);
	    });
    }
}
