<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddStatWeekYearForBds extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('booking_day_statistics', function (Blueprint $table) {
            //
            $table->integer('stat_week_year')->after('stat_week');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('booking_day_statistics', function (Blueprint $table) {
            //
        });
    }
}
