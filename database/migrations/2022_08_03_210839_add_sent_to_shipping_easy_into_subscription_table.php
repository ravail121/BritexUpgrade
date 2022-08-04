<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddSentToShippingEasyIntoSubscriptionTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
	    Schema::table('subscription', function (Blueprint $table) {
		    $table->tinyInteger('sent_to_shipping_easy')->after('requested_zip')->default(0);
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
			    'sent_to_shipping_easy',
		    ]);
	    });
    }
}
