<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddAndChangeColumnForBa extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('booking_airlines', function (Blueprint $table) {
            //
            $table->renameColumn("airline","d_airline");
            $table->renameColumn("flight_number","d_flight");
            $table->string("a_airline",255);
            $table->string("a_flight",255);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('booking_airlines', function (Blueprint $table) {
            //
        });
    }
}
