<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddPay2AmountInBth extends Migration
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
            $table->decimal('pay2_amount',8,2)->default(0.0)->after('pay2_id');
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
        });
    }
}
