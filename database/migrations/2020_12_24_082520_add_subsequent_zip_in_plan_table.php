<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddSubsequentZipInPlanTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
	    Schema::table('plan', function (Blueprint $table) {
		    $table->tinyInteger('subsequent_zip')->after('require_device_info')->default('0');
	    });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
	    Schema::table('plan', function (Blueprint $table) {
		    $table->dropColumn([
			    'subsequent_zip',
		    ]);
	    });
    }
}
