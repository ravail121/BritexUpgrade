<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddSurchargeCsvInvoiceEnabledToCustomerTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
	    Schema::table('customer', function (Blueprint $table) {
		    $table->boolean('csv_invoice_enabled')
		          ->after('shipping_zip')
		          ->default(false)
		          ->comment('CSV Invoice Enabled');
			$table->integer('surcharge')
		          ->after('csv_invoice_enabled')
		          ->default(0)
		          ->comment('Surcharge Percentage');
	    });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
	    Schema::table('customer', function (Blueprint $table) {
		    $table->dropColumn( [
			    'csv_invoice_enabled',
			    'surcharge'
		    ] );
	    });
    }
}
