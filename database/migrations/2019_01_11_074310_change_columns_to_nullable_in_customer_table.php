<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangeColumnsToNullableInCustomerTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('customer', function (Blueprint $table) {
            $table->date('subscription_start_date')->nullable()->change();
            $table->date('billing_start')->nullable()->change();
            $table->date('billing_end')->nullable()->change();
            $table->integer('primary_payment_method')->nullable()->change();
            $table->integer('primary_payment_card')->nullable()->change();
            $table->boolean('account_suspended')->default(0)->change();
            $table->text('billing_address1')->nullable()->change();
            $table->text('billing_address2')->nullable()->change(); 
            $table->text('billing_city')->nullable()->change();
            $table->string('billing_state_id', 2)->nullable()->change();
            $table->text('shipping_address2')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('customer', function (Blueprint $table) {
            $table->date('subscription_start_date')->nullable(false)->change();
            $table->date('billing_start')->nullable(false)->change();
            $table->date('billing_end')->nullable(false)->change();
            $table->integer('primary_payment_method')->nullable(false)->change();
            $table->integer('primary_payment_card')->nullable(false)->change();
            $table->boolean('account_suspended')->default(NULL)->change();
            $table->text('billing_address1')->nullable(false)->change();
            $table->text('billing_address2')->nullable(false)->change(); 
            $table->text('billing_city')->nullable(false)->change();
            $table->string('billing_state_id', 2)->nullable(false)->change();
            $table->text('shipping_address2')->nullable(false)->change();
        });
    }
}
