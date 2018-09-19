<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTableSales extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('sales', function (Blueprint $table) {
            $table->increments('id');
            $table->string('sale_id');
            $table->integer('user_id');
            $table->string('region');
            $table->string('country');
            $table->timestamps();
        });

        DB::update("ALTER TABLE sales AUTO_INCREMENT = 100000;");
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::drop('sales');
    }
}
