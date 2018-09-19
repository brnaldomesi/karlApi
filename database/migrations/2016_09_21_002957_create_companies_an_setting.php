<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCompaniesAnSetting extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('company_an_settings', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('company_id');
            $table->tinyInteger('ln')->default(0);
            $table->tinyInteger('gn')->default(0);
            $table->tinyInteger('combine')->default(0);
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
        Schema::drop('company_settings');
    }
}
