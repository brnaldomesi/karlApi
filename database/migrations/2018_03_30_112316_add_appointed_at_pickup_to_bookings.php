<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddAppointedAtPickupToBookings extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //
        Schema::table('bookings', function (Blueprint $table) {
            //
            $table->timestamp('appointed_at_pickup')->after('appointed_at');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
        Schema::table('bookings', function (Blueprint $table) {
            //
            $table->dropColumn('appointed_at_pickup')->after('appointed_at');
        });
    }
}
