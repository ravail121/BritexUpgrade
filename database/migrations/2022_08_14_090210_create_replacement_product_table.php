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
	        $table->unsignedInteger('company_id');
	        $table->foreign('company_id')->references('id')->on('company');
	        $table->integer('product_id')->nullable();
	        $table->string('product_type', 50);
	        $table->unique(['product_id', 'product_type'], 'product_id_product_type_unique');
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
	    Schema::table('replacement_products', function (Blueprint $table) {
		    $table->dropUnique('product_id_product_type_unique');
	    });
        Schema::dropIfExists('replacement_products');
    }
}
