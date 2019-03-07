<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCreditToInvoiceTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('credit_to_invoice', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('credit_id')->nullable();
            $table->unsignedInteger('invoice_id')->default(0);
            $table->decimal('amount', 9, 2);
            $table->text('description');
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
        Schema::dropIfExists('credit_to_invoice');
    }
}
