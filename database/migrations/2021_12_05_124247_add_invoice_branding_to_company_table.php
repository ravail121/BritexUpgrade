<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddInvoiceBrandingToCompanyTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
	    Schema::table('company', function (Blueprint $table) {
		    $table->text('invoice_background_text_color')
		          ->after('enable_bulk_order')
		          ->nullable()
		          ->comment('Background text color for Invoice');
		    $table->text('invoice_normal_text_color')
		          ->after('invoice_background_text_color')
		          ->nullable()
		          ->comment('Normal text color for Invoice');
		    $table->text('invoice_account_summary_primary_color')
		          ->after('invoice_normal_text_color')
		          ->nullable()
		          ->comment('Account Summary Primary Color for Invoice');
		    $table->text('invoice_account_summary_secondary_color')
		          ->after('invoice_account_summary_primary_color')
		          ->nullable()
		          ->comment('Account Summary Secondary Color for Invoice');
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
			    'invoice_background_text_color',
			    'invoice_normal_text_color',
			    'invoice_account_summary_primary_color',
			    'invoice_account_summary_secondary_color'
		    ] );
	    });
    }
}
