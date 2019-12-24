<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddCompanyIdToNodeAndFanTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('fan', function (Blueprint $table) {
            $table->unsignedInteger('company_id')->after('id')->nullable();
            $table->foreign('company_id')->references('id')->on('company')
            ->onUpdate('cascade')
            ->onDelete('cascade');
        });

        Schema::table('node', function (Blueprint $table) {
            $table->unsignedInteger('company_id')->after('id')->nullable();
            $table->foreign('company_id')->references('id')->on('company')
            ->onUpdate('cascade')
            ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('fan', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->dropColumn([
                'company_id'
            ]);
        });

        Schema::table('node', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->dropColumn([
                'company_id'
            ]);
        });
    }
}
