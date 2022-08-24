<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateReplacementProductTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('replacement_products', function (Blueprint $table) {
            $table->increments('id');
	        $table->text('name');
	        $table->unsignedInteger('company_id');
	        $table->foreign('company_id')->references('id')->on('company');
	        $table->integer('product_id')->nullable();
	        $table->text('product_type');
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
        Schema::dropIfExists('replacement_products');
    }
}
