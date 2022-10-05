<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddDateSubmittedColoumInPortTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('port', function (Blueprint $table) {
            $table->date('date_submitted')->after('ssn_taxid')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('port', function (Blueprint $table) {
            $table->dropColumn([
                'date_submitted'
            ]);
        });
    }
}
