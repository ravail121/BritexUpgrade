<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddIndexToCostumerTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
	    Schema::table('customer', function (Blueprint $table) {
		    $table->index('company_id');
		    $table->index('fname');
		    $table->index('lname');
		    $table->index('phone');
		    $table->index('email');
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
		    $table->dropIndex('company_id');
		    $table->dropIndex('fname');
		    $table->dropIndex('lname');
		    $table->dropIndex('phone');
		    $table->dropIndex('email');
	    });
    }
}
