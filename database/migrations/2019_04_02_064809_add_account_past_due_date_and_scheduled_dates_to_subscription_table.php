<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddAccountPastDueDateAndScheduledDatesToSubscriptionTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('subscription', function (Blueprint $table) {
            $table->date('account_past_due_date')->after('upgrade_downgrade_date_submitted')->nullable();
            $table->date('scheduled_suspend_date')->after('downgrade_date')->nullable();
            $table->date('scheduled_close_date')->after('scheduled_suspend_date')->nullable();
        });
    }

    /**
     * Reverse the migrations
     *
     * @return void
     */
    public function down()
    {
        Schema::table('subscription', function (Blueprint $table) {
            $table->dropColumn([
                'account_past_due_date',
                'scheduled_suspend_date',
                'scheduled_close_date'
            ]);
        });
    }
}
