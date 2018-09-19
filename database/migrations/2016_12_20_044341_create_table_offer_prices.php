<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTableOfferPrices extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('offer_prices', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('offer_id');
            $table->integer('company_id');
            $table->tinyInteger('calc_method');
            $table->integer('invl_start');
            $table->integer('invl_end');
            $table->double('price');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('offer_prices');
    }
}
