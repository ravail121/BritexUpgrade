<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddOwnSimCardOptionToPlanTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
	    Schema::table('plan', function (Blueprint $table) {
		    $table->tinyInteger('own_sim_card_option')
		          ->after('subsequent_zip')
		          ->default('1')
		          ->comment("Toggles 'I will bring my own sim option'");
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
			    'own_sim_card_option',
		    ]);
	    });
    }
}
