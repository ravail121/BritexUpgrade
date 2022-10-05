<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddRequestedZipColumnToSubscriptionTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
	    Schema::table('subscription', function(Blueprint $table){
		    $table->string('requested_zip')->after('sent_to_readycloud')->nullable();
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
			    'requested_zip'
		    ]);
	    });
    }
}
