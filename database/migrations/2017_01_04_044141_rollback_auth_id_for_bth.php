<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class RollbackAuthIdForBth extends Migration
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
            $table->string('auth_id')->after('pay1_id')->default(0);
            $table->string('pay_auth')->nullable()->after('booking_id');
            $table->string('capture_id')->nullable()->after('pay1_refund');
            $table->tinyInteger('capture_success')->nullable()->after('capture_id');
            $table->double('capture_amount')->default(0.00)->after('capture_success');
            $table->string('repay1_id')->nullable()->after('capture_amount');
            $table->double('repay1_amount')->nullable()->after('repay1_id');
            $table->tinyInteger('repay1_success')->nullable()->after('repay1_amount');
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
            $table->dropColumn([
                'auth_id',
                'pay_auth',
                'capture_id',
                'capture_success',
                'capture_amount',
                'repay1_id',
                'repay1_amount',
                'repay1_success'
            ]);
        });
    }
}
