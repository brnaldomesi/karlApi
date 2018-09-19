<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTabelTransRecord extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('trans_records', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->string('charge_id');
            $table->string('trans_id');
            $table->string('trans_balance_id');
            $table->bigInteger('available_on');
            $table->string('booking_id');
            $table->string('company_id');
            $table->tinyInteger('trans_type');
            $table->double('trans_amount');
            $table->string('trans_ccy');
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
        Schema::drop('trans_records');
    }
}
