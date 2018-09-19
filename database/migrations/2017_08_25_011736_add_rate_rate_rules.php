<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddRateRateRules extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('rate_rules', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->double('rate')->after('invl_end');
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
            $table->dropColumn('rate');
        });
    }
}
