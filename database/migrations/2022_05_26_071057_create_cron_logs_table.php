<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCronLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('cron_logs', function (Blueprint $table) {
	        $table->bigIncrements('id');
	        $table->text('name');
	        $table->text('status');
	        $table->longText('payload')->nullable();
	        $table->longText('response')->nullable();
	        $table->timestamp('ran_at')->useCurrent();
			$table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('cron_logs');
    }
}
