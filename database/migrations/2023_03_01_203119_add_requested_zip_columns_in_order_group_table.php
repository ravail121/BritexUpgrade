<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddRequestedZipColumnsInOrderGroupTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
	    Schema::table('order_group', function(Blueprint $table){
		    $table->string('requested_zip')->nullable();
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
			    'requested_zip'
		    ]);
	    });
    }
}
