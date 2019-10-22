<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddFieldsToCompanyTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
         Schema::table('company', function (Blueprint $table) {
            $table->bigInteger('support_phone_number')->after('smtp_password')->nullable();
            $table->string('logo')->after('email_footer')->nullable();
            $table->string('primary_contact_name')->after('logo')->nullable();
            $table->Integer('primary_contact_phone_number')->after('primary_contact_name')->nullable();
            $table->string('primary_contact_email_address')->after('primary_contact_phone_number')->nullable();
            $table->string('address_line_1')->after('primary_contact_email_address')->nullable();
            $table->string('address_line_2')->after('address_line_1')->nullable();
            $table->string('city')->after('address_line_2')->nullable();
            $table->string('state')->after('city')->nullable();
            $table->string('zip')->after('state')->nullable();
            $table->string('usaepay_api_key')->after('zip')->nullable();
            $table->string('usaepay_username')->after('usaepay_api_key')->nullable();
            $table->string('usaepay_password')->after('usaepay_username')->nullable();
            $table->string('readycloud_api_key')->after('usaepay_password')->nullable();
            $table->string('readycloud_username')->after('readycloud_api_key')->nullable();
            $table->string('readycloud_password')->after('readycloud_username')->nullable();
            $table->string('tbc_username')->after('readycloud_password')->nullable();
            $table->string('tbc_password')->after('tbc_username')->nullable();
            $table->string('apex_username')->after('tbc_password')->nullable();
            $table->string('apex_password')->after('apex_username')->nullable();
            $table->string('premier_username')->after('apex_password')->nullable();
            $table->string('premier_password')->after('premier_username')->nullable();
            $table->string('opus_username')->after('premier_password')->nullable();
            $table->string('opus_password')->after('opus_username')->nullable();
            $table->string('reseller_status')->after('opus_password')->nullable();
            $table->double('default_voice_reg_fee')->after('regulatory_label')->nullable();
            $table->double('default_data_reg_fee')->after('default_voice_reg_fee')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('company', function (Blueprint $table) {
            $table->dropColumn([
                'support_phone_number','logo','primary_contact_name','primary_contact_phone_number','primary_contact_email_address','address_line_1','address_line_2','city','state','zip','usaepay_api_key','usaepay_username','usaepay_password','readycloud_api_key','readycloud_username','readycloud_password','tbc_username','tbc_password','apex_username','apex_password','premier_username','premier_password','opus_username','opus_password','reseller_status', 'default_voice_reg_fee','default_data_reg_fee'
            ]);
        });
    }
}