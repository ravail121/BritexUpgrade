<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddSolidLineColorToCompanyTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
	    Schema::table('company', function (Blueprint $table) {
		    $table->text('invoice_solid_line_color')
		          ->after('invoice_account_summary_secondary_color')
		          ->nullable()
		          ->comment('Solid lines color for Invoice');
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
			    'invoice_solid_line_color'
		    ] );
	    });
    }
}
