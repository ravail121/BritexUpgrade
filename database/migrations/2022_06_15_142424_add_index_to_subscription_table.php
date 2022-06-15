<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddIndexToSubscriptionTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
	    Schema::table('subscription', function (Blueprint $table) {
		    $table->index('status');
		    $table->index('sub_status');
		    $table->index('customer_id');
	    });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
	    Schema::table('subscription', function (Blueprint $table) {
		    $table->dropIndex('status');
		    $table->dropIndex('sub_status');
		    $table->dropIndex('customer_id');
	    });
    }
}
