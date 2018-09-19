<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTableOrders extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->unsignedInteger('booking_id');
            $table->unsignedInteger('order_state');
            $table->unsignedInteger('trip_state');
            $table->timestamp('departure_time')->nullable();
            $table->timestamp('reach_time')->nullable();
            $table->timestamp('start_time')->nullable();
            $table->timestamp('finish_time')->nullable();
            $table->timestamp('settle_time')->nullable();
            $table->double('actual_distance');
            $table->unsignedInteger('actual_time');
            $table->decimal('actual_fee',8,2);
            $table->unsignedInteger('feedbacked');
            $table->unsignedInteger('invoice_sent');
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
        Schema::drop('orders');
    }
}
