<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * Mark STAT
 * Class CreateTableStatistics
 */
class CreateTableBookingDayStatistics extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('booking_day_statistics', function (Blueprint $table) {
            $table->increments('id');
            $table->integer("company_id");
            $table->unsignedInteger('booking_count')->default(0);
            $table->unsignedInteger('on_time')->default(0);
            $table->unsignedInteger('exe_an_count')->default(0);
            $table->unsignedInteger('out_an_count')->default(0);
            $table->unsignedInteger('an_count')->default(0);
            $table->unsignedInteger('p2p_count')->default(0);
            $table->unsignedInteger('hour_count')->default(0);
            $table->unsignedInteger('cq_count')->default(0);
            $table->unsignedInteger('appearance_count')->default(0);
            $table->unsignedInteger('professionalism_count')->default(0);
            $table->unsignedInteger('driving_count')->default(0);
            $table->unsignedInteger('cleanliness_count')->default(0);
            $table->unsignedInteger('quality_count')->default(0);
            $table->double('total_income')->default(0);
            $table->double('total_plate')->default(0);
            $table->double('total_an_fee')->default(0);
            $table->timestamp('settle_date')->nullable();
            $table->unsignedInteger('settle_day')->default(0);
            $table->unsignedInteger('settle_month')->default(0);
            $table->unsignedInteger('settle_year')->default(0);
            $table->unsignedInteger('settle_week')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('booking_day_statistics');
    }
}
