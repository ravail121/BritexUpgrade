<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddColumnsToCustomerCreditCardsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('customer_credit_cards', function (Blueprint $table) {
            $table->string('token')->after('id');
            $table->text('billing_address2')->after('billing_address1')->nullable();
            $table->text('billing_city')->after('billing_address2');
            $table->char('billing_state_id', 2)->after('billing_city');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('customer_credit_cards', function (Blueprint $table) {
            $table->dropColumn([
                'token',
                'billing_address2',
                'billing_city',
                'billing_state_id',
            ]);
        });
    }
}
