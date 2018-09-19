<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTableCarBrands extends Migration
{
    /**
     * Run the migrations.
     * name 汽车品牌名称
     * sort 汽车品牌名称拼音或名字排序
     * @return void
     */
    public function up()
    {
        Schema::create('car_brands', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->string('name',255);
            $table->string('sort',255);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('car_brands');
    }
}
