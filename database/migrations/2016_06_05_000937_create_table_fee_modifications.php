<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTableFeeModifications extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('fee_modifications', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->unsignedInteger('order_id');
            $table->decimal('delta_fee')->default(0,0);
            $table->decimal('delta_tax')->default(0,0);
            $table->string('comment',255);
            $table->unsignedInteger('active');
            $table->unsignedInteger('modifier_id');
            $table->unsignedInteger('modifier_type');
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
        Schema::drop('fee_modifications');
    }
}
