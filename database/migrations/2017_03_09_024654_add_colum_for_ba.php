<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddColumForBa extends Migration
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
            $table->string("d_airline_code")->default("")->after("d_airline");
            $table->string("a_airline_code")->default("")->after("a_airline");
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
