<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTableCarCategories extends Migration
{
    /**
     * Run the migrations.
     * name 汽车类型名称
     * description 描述
     * priority 排序优先级
     * @return void
     */
    public function up()
    {
        Schema::create('car_categories', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->string('name',255);
            $table->string('description',1024);
            $table->unsignedInteger('priority');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('car_categories');
    }
}
