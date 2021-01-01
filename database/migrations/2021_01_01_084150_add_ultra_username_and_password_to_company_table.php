<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddUltraUsernameAndPasswordToCompanyTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
	    Schema::table('company', function (Blueprint $table) {
		    $table->string( 'ultra_username' )->after( 'goknows_api_key' )->nullable();
		    $table->string( 'ultra_password' )->after( 'ultra_username' )->nullable();
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
			    'ultra_username',
			    'ultra_password'
		    ] );
	    });
    }
}
