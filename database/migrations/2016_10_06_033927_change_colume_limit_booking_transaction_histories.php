<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangeColumeLimitBookingTransactionHistories extends Migration
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
            $table->decimal('pay1_amount',18,2)->change();
            $table->decimal('pay2_amount',18,2)->change();
            $table->decimal('pay1_refund_amount',18,2)->change();
            $table->decimal('pay2_refund_amount',18,2)->change();
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
