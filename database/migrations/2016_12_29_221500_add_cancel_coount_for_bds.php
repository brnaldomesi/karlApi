<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddCancelCoountForBds extends Migration
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
            $table->integer('cancel_count')->default(0)->after('on_time');
            $table->integer('invalid_count')->default(0)->after('cancel_count');
            $table->integer('trouble_count')->default(0)->after('invalid_count');
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
