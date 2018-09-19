<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class RemoveDtPtForOffers extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('offers', function (Blueprint $table) {
            //
            $table->dropColumn('pre_time');
            $table->dropColumn("delay_time");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('offers', function (Blueprint $table) {
            //
            $table->integer('pre_time')->default("60")->after("type");
            $table->integer("delay_time")->default("60")->after("pre_time");
        });
    }
}
