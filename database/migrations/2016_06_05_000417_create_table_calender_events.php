<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTableCalenderEvents extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('calendar_events', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->unsignedInteger('calendar_id');
            $table->timestamp('start_time');
            $table->timestamp('end_time');
            $table->string('content',1024);
            $table->unsignedInteger('re_company_id');
            $table->unsignedInteger('re_type');
            $table->unsignedInteger('re_owner_id');
            $table->unsignedInteger('creator_id');
            $table->unsignedInteger('creator_type');
            $table->unsignedInteger('enable');
            $table->unsignedInteger('repeat_id')->nullable();
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
        Schema::drop('calendar_events');
    }
}
