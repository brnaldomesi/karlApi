<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTableCompaniesAnnex extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('company_annexes', function (Blueprint $table) {
            $table->increments('id');
            $table->integer("company_id");
            $table->string("ios_app",1024)->nullable();
            $table->string("android_app",1024)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('company_annexes');
    }
}
