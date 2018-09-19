<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddRatingForDrivers extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('drivers', function (Blueprint $table) {
            //
            $table->integer('count_appear')->after('delay_time');
            $table->integer('count_profess')->after('count_appear');
            $table->integer('count_drive')->after('count_profess');
            $table->integer('count_clean')->after('count_drive');
            $table->integer('count_rating')->after('count_clean');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('drivers', function (Blueprint $table) {
            //
        });
    }
}
