<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterFieldsToNullableInEmailTemplateTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('email_template', function (Blueprint $table) {
            $table->string('notes')->nullable()->change();
            $table->string('reply_to')->nullable()->change();
            $table->string('cc')->nullable()->change();
            $table->string('bcc')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('email_template', function (Blueprint $table) {
            $table->string('notes')->nullable(false)->change();
            $table->string('reply_to')->nullable(false)->change();
            $table->string('cc')()->nullable(false)->change();
            $table->string('bcc')->nullable(false)->change();
        });
    }
}
