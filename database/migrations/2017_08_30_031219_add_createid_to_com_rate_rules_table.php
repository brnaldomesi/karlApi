<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddCreateidToComRateRulesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('com_rate_rules', function (Blueprint $table) {
            //


            $table->integer('take_effect')->after('end_time');
            $table->integer('creator_id')->after('end_time');
            $table->integer('creator_type')->after('end_time');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('com_rate_rules', function (Blueprint $table) {
            //
            $table->dropColumn('creator_type');
            $table->dropColumn('creator_id');
            $table->dropColumn('take_effect');
        });
    }
}
