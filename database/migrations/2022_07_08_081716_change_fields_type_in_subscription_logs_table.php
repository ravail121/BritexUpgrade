<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangeFieldsTypeInSubscriptionLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
	    Schema::table('subscription_log', function (Blueprint $table) {
		    $table->text('category')->nullable()->change();
		    $table->integer('product_id')->nullable()->change();
		    $table->text('description')->nullable()->change();
		    $table->integer('old_product')->nullable()->change();
		    $table->integer('new_product')->nullable()->change();
		    $table->dropColumn('date');
	    });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
	    Schema::table('subscription_log', function (Blueprint $table) {
		    $table->text('category')->change();
		    $table->integer('product_id')->change();
		    $table->text('description')->change();
		    $table->integer('old_product')->change();
		    $table->integer('new_product')->change();
	    });
    }
}
