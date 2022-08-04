<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddShippingEasyApiKeyInCompanyTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
	    Schema::table('company', function (Blueprint $table) {
		    $table->string( 'shipping_easy_api_key' )->after( 'invoice_solid_line_color' )->nullable();
		    $table->string( 'shipping_easy_api_secret' )->after( 'shipping_easy_api_key' )->nullable();
		    $table->string( 'shipping_easy_store_api_key' )->after( 'shipping_easy_api_secret' )->nullable();
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
		    $table->dropColumn([
			    'shipping_easy_api_key',
			    'shipping_easy_api_secret',
			    'shipping_easy_store_api_key'
		    ]);
	    });
    }
}
