<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddClosedDateColumnToCustomerStandaloneSimTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
	    Schema::table('customer_standalone_sim', function (Blueprint $table) {
		    $table->date( 'closed_date' )
		          ->nullable()
		          ->comment('Closed date to allow unassigning the sim');
	    });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
	    Schema::table('customer_standalone_sim', function (Blueprint $table) {
		    $table->dropColumn( [
			    'closed_date'
		    ] );
	    });
    }
}
