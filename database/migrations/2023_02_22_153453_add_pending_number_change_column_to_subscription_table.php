<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddPendingNumberChangeColumnToSubscriptionTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
	    Schema::table('subscription', function (Blueprint $table) {
		    $table->tinyInteger('pending_number_change')->after('sent_to_shipping_easy')->default(0);
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
		    $table->dropColumn([
			    'pending_number_change'
		    ]);
	    });
    }
}
