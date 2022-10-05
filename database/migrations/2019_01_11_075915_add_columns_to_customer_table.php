<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddColumnsToCustomerTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('customer', function (Blueprint $table) {
            $table->boolean('business_verified')->nullable()->after('business_verification_id');
            $table->string('fname')->after('business_verified');
            $table->string('lname')->after('fname');
            $table->string('email')->after('lname');
            $table->string('company_name')->nullable()->after('email');
            $table->string('password')->after('lname');
            $table->bigInteger('phone')->after('password');
            $table->bigInteger('alternate_phone')->nullable()->after('phone');
            $table->string('pin')->after('alternate_phone');
            $table->string('shipping_zip')->after('shipping_state_id');
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
             $table->dropColumn([
                'business_verified',
                'fname',
                'lname',
                'email',
                'company_name',
                'password',
                'phone',
                'alternate_phone',
                'pin',
                'shipping_zip',
            ]);
        });
    }
}
