<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddIsOneTimeColumnToAddonTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
	    Schema::table('addon', function (Blueprint $table) {
		    $table->boolean('is_one_time')->after('bot_code')->nullable()->comment('Flag to indicate if the addon is one time or recurring');
		});
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
	    Schema::table('addon', function (Blueprint $table) {
		    $table->dropColumn('is_one_time');
	    });
    }
}
