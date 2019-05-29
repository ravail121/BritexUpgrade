<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddFielsdToBanGroupTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('ban_group', function (Blueprint $table) {
            $table->string('name')->after('ban_id')->nullable();
            $table->string('data_cap')->after('number')->nullable();
            $table->string('line_limit')->after('data_cap')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('ban_group', function (Blueprint $table) {
            $table->dropColumn([
                'name','data_cap','line_limit'
            ]);
        });
    }
}
