<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class RemoveRateComRateRules extends Migration
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
            $table->dropColumn(['rate_id','rate']);
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
            $table->integer('rate_id')->after('company_id');
            $table->integer('rate')->after('rate_id');
        });
    }
}
