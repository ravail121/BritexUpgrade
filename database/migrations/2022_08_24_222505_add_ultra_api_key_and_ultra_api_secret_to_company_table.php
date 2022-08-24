<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddUltraApiKeyAndUltraApiSecretToCompanyTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
	    Schema::table('company', function (Blueprint $table) {
		    $table->text('ultra_api_key')->nullable()->comment('API Key for Ultra');
		    $table->text('ultra_api_secret')->nullable()->comment('API Secret for Ultra');
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
			    'ultra_api_key',
			    'ultra_api_secret'
		    ]);
	    });
    }
}
