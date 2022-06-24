<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddIndexToCronLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
	    Schema::table('cron_logs', function (Blueprint $table) {
		    $table->string('name', 100)->change();
		    $table->string('status', 100)->change();
		    $table->index('name');
		    $table->index('status');
	    });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
	    Schema::table('cron_logs', function (Blueprint $table) {
		    $table->dropIndex('name');
		    $table->dropIndex('status');
	    });
    }
}
