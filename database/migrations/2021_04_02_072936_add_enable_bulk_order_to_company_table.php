<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddEnableBulkOrderToCompanyTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
	    Schema::table('company', function (Blueprint $table) {
		    $table->boolean( 'enable_bulk_order' )
		          ->default('0')
		          ->after( 'ultra_password' )
		          ->comment('Enable Bulk Order for the company');
	    });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
	    Schema::table('company', function (Blueprint $table) {
		    $table->dropColumn( [
			    'enable_bulk_order'
		    ] );
	    });
    }
}
