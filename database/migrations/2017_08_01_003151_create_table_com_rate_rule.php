<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTableComRateRule extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('com_rate_rules', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('company_id');
            $table->integer('rate_id');
            $table->integer('rate');
            $table->bigInteger('start_time');
            $table->bigInteger('end_time');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('com_rate_rules');
    }
}
