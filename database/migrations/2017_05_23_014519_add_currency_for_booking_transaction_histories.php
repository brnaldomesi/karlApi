<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddCurrencyForBookingTransactionHistories extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('booking_transaction_histories', function (Blueprint $table) {
            //
            $table->string("ccy")->after("booking_id")->default("usd");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('booking_transaction_histories', function (Blueprint $table) {
            //
            $table->dropColumn(['ccy']);
        });
    }
}
