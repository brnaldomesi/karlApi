<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTableCreditCardZipCodeSetting extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('card_zip_code_setting', function (Blueprint $table) {
            $table->string('country_code');
            $table->tinyInteger("proving");
        });

        DB::insert(
            "insert into card_zip_code_setting values('US',1),('FR',0),('GB',0)"
        );
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('card_zip_code_setting');
    }
}
