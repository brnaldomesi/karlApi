<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTableCompanyPushMsgs extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('company_push_msgs', function (Blueprint $table) {
            $table->increments('id');
            $table->integer("company_id");
            $table->string("text_01",1024);
            $table->string("text_02",1024);
            $table->string("text_03",1024);
            $table->string("text_04",1024);
            $table->string("text_05",1024);
            $table->string("text_06",1024);
            $table->string("text_07",1024);
            $table->string("text_08",1024);
            $table->string("text_09",1024);
            $table->string("text_10",1024);
            $table->string("text_11",1024);
            $table->string("text_12",1024);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('company_push_msgs');
    }
}
