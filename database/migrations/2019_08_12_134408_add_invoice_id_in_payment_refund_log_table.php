<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddInvoiceIdInPaymentRefundLogTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
            Schema::table('payment_refund_log', function (Blueprint $table) {
                $table->unsignedInteger('invoice_id')->after('payment_log_id')->nullable();
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
        Schema::table('payment_refund_log', function (Blueprint $table) {
            $table->dropColumn([
                'goknows_api_key'
            ]);
        });
    }
}
