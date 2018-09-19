<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddRateComRateRules extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('com_rate_rules', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->double('rate')->after('company_id');
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
            $table->dropColumn('rate');
        });
    }
}
