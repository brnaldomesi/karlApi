<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddLatLngForOrders extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('orders', function (Blueprint $table) {
            //
            $table->double('last_lat')->after('unit');
            $table->double('last_lng')->after('unit');
            $table->double('last_distance')->after('unit');
            $table->string('last_address',2048)->after('unit');
            $table->double('last_speed')->after('unit');
            $table->bigInteger('last_report_time')->after('unit');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('orders', function (Blueprint $table) {
            //
            $table->dropColumn([
                'last_lat',
                'last_lng',
                'last_distance',
                'last_address',
                'last_report_time',
                'last_speed'
            ]);
        });
    }
}
