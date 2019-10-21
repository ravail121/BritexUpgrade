<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddInvoiceIdToCreditTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('credit', function (Blueprint $table) {
            $table->unsignedInteger('invoice_id')->after('staff_id')->nullable();
            $table->foreign('invoice_id')->references('id')->on('invoice');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('credit', function (Blueprint $table) {
            $table->dropColumn([
                'invoice_id'
            ]);
        });
    }
}
