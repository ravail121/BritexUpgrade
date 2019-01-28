<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddThreeColumnsToCustomerCreditCardsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('customer_credit_cards', function (Blueprint $table) {
            $table->tinyInteger('default')->after('customer_id')->default(0);
            $table->text('last4')->after('expiration');
            $table->text('card_type')->after('last4');
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
                'default',
                'last4',
                'card_type',
            ]);
        });
    }
}
