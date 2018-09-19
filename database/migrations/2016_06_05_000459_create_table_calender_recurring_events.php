<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTableCalenderRecurringEvents extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('calendar_recurring_events', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->unsignedInteger('owner_id');
            $table->unsignedInteger('owner_type');
            $table->unsignedInteger('creator_id');
            $table->unsignedInteger('creator_type');
            $table->timestamp('start_time');
            $table->unsignedInteger('duration_time');
            $table->string('content',1024);
            $table->unsignedInteger('repeat_type');
            $table->unsignedInteger('time_zone');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('calendar_recurring_events');
    }
}
