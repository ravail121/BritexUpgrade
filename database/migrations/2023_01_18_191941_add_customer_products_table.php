<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddCustomerProductsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
	    Schema::create('customer_products', function (Blueprint $table) {
		    $table->increments('id');
		    $table->unsignedInteger('customer_id')->nullable()->comment('Customer ID');
		    $table->foreign('customer_id')->references('id')->on('customer')->onDelete('cascade');
		    $table->integer('product_type')->comment('Product Type');
		    $table->integer('product_id')->nullable()->comment('Product ID');
		    $table->unique(['customer_id', 'product_type', 'product_id'], 'customer_product_unique');
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
	    Schema::table('customer_products', function (Blueprint $table) {
		    $table->dropForeign('customer_products_customer_id_foreign');
		    $table->dropUnique('customer_product_unique');
	    });
	    Schema::dropIfExists('customer_products');
    }
}
