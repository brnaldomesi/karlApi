<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddColumnForBookingDayStat extends Migration
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
            $table->integer('total_bookings')->default(0)->after('company_id');
            $table->renameColumn('booking_count','completed_bookings');
            $table->double('total_est_amount')->default(0)->after('quality_count');
            $table->renameColumn('settle_date','stat_date');
            $table->renameColumn('settle_day','stat_day');
            $table->renameColumn('settle_month','stat_month');
            $table->renameColumn('settle_week','stat_week');
            $table->renameColumn('settle_year','stat_year');
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
