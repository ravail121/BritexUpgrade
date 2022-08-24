<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddSubscriptionIdToCustomerStandaloneDeviceTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
	    Schema::table('customer_standalone_device', function (Blueprint $table) {
		    $table->unsignedInteger( 'subscription_id' )
		          ->nullable()
		          ->comment('Subscription ID for the device');
		    $table->foreign('subscription_id')
		          ->references('id')->on('subscription')
		          ->onUpdate('cascade')
		          ->onDelete('cascade');
	    });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
	    Schema::table('customer_standalone_device', function (Blueprint $table) {
		    $table->dropForeign('customer_standalone_device_subscription_id_foreign');
		    $table->dropColumn( [
			    'subscription_id'
		    ] );
	    });
    }
}
