<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTableCreditCards extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('credit_cards', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->unsignedInteger('owner_id');
            $table->unsignedInteger('type');

            $table->unsignedInteger('card_type');
            $table->string('card_number',255);
            $table->string('expire_month',255);
            $table->string('expire_year',255);
            $table->string('cvv2',255);
            $table->string('first_name',255);
            $table->string('last_name',255);
            $table->unsignedInteger('last_use');

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
        Schema::drop('credit_cards');
    }
}
