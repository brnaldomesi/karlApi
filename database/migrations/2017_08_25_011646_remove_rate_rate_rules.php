<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class RemoveRateRateRules extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('rate_rules', function (Blueprint $table) {
            //
            $table->dropColumn('rate');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('rate_rules', function (Blueprint $table) {
            //
            $table->integer('rate')->after('invl_end');
        });
    }
}
