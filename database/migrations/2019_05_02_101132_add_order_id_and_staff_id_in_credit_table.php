<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddOrderIdAndStaffIdInCreditTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('credit', function (Blueprint $table) {
            $table->unsignedInteger('order_id')->after('description')->nullable();
            $table->unsignedInteger('staff_id')->after('order_id')->nullable();
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
                'order_id', 'staff_id'
            ]);
        }); 
    }
}
