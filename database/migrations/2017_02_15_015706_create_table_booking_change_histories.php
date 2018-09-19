<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTableBookingChangeHistories extends Migration
{
    /**
     * Run the migrations
     * @return void
     */
    public function up()
    {
        Schema::create('booking_change_histories', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->integer('company_id');
            $table->integer('admin_id');
            $table->integer('booking_id');
            $table->text('booking_info');
            $table->tinyInteger('action_type');
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
        Schema::drop('booking_change_histories');
    }
}
