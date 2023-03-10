<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddIndexToUsageDataTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {

	    Schema::table('usage_data', function (Blueprint $table) {
		    $table->string('simnumber')->change();
		    $table->index('simnumber');
	    });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
	    Schema::table('usage_data', function (Blueprint $table) {
		    $table->dropIndex('usage_data_simnumber_index');
	    });
    }
}
