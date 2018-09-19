<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangeTableCreditCard extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('credit_cards', function (Blueprint $table) {
            //
            $table->string('card_token',1024)->after('type');
            $table->dropColumn([
                'card_number',
                'expire_month',
                'expire_year',
                'cvv2',
                'first_name',
                'last_name'
            ]);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('credit_cards', function (Blueprint $table) {
            //
            $table->dropColumn(['card_token']);
            $table->string('card_number',255);
            $table->string('expire_month',255);
            $table->string('expire_year',255);
            $table->string('cvv2',255);
            $table->string('first_name',255);
            $table->string('last_name',255);
            $table->unsignedInteger('last_use');
        });
    }
}
