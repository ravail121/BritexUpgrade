    <?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCustomerStandaloneDeviceTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('customer_standalone_device', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('customer_id')->nullable();
            $table->unsignedInteger('device_id')->nullable();
            $table->unsignedInteger('order_id')->nullable();
            $table->string('status')->default('shipping');
            $table->bigInteger('tracking_num');
            $table->text('imei');
            $table->timestamps();

            $table->foreign('customer_id')
                ->references('id')->on('customer')
                ->onUpdate('cascade')
                ->onDelete('cascade');

            $table->foreign('device_id')
                ->references('id')->on('device')
                ->onUpdate('cascade')
                ->onDelete('cascade');

            $table->foreign('order_id')
                ->references('id')->on('order')
                ->onUpdate('cascade')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('customer_standalone_device');
    }
}
