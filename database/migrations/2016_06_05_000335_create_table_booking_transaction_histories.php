<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTableBookingTransactionHistories extends Migration
{
    /**
     * Run the migrations.
     * id ->自增id
     * booking_id -> booking id
     * pay1_amount -> 预付费用
     * pay1_id -> 预付费用平台支付id
     * pay1_platform -> 支付平台 枚举详见{@link \App\BookingTransactionHistory}
     * pay1_success -> 预付款是否成功
     * pay1_auth_id -> 预付费的认证id,(PayPal)
     * pay1_refund -> 预付是否退款
     * pay1_refund_amount -> 预付退款金额
     * pay1_refund_success -> 预付款退款是否成功
     * capture_id -> 抓取id
     * capture_amount -> 抓取费用
     * capture_success -> 抓取费用是否成功
     * pay2_amount -> 超出费用
     * pay2_id -> 超出费用平台支付id
     * pay2_platform -> 支付平台 枚举详见{@link \App\BookingTransactionHistory}
     * pay2_success -> 预付款是否成功
     * pay2_refund -> 超出费用是否退款
     * pay2_refund_amount -> 超出费用退款金额
     * pay2_refund_success -> 超出费用款退款是否成功
     * tva->税率
     * @return void
     */
    public function up()
    {
        Schema::create('booking_transaction_histories', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->unsignedInteger('booking_id');
            $table->decimal('pay1_amount',8,2);
            $table->string('pay1_id');
            $table->integer('pay1_platform');
            $table->unsignedInteger('pay1_success');
            $table->string('pay1_auth_id')->nullable();
            $table->integer('pay1_refund')->nullable();
            $table->decimal('pay1_refund_amount',8,2)->default(0,0);
            $table->unsignedInteger('pay1_refund_success')->nullable();
            $table->string('capture_id')->nullable();
            $table->decimal('capture_amount',8,2)->nullable();
            $table->unsignedInteger('capture_success')->nullable();
            $table->string('pay2_id')->nullable();
            $table->integer('pay2_platform')->nullable();
            $table->unsignedInteger('pay2_success')->nullable();
            $table->integer('pay2_refund')->nullable();
            $table->decimal('pay2_refund_amount',8,2)->default(0,0);
            $table->unsignedInteger('pay2_refund_success')->nullable();
            $table->decimal('tva');
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
        Schema::drop('booking_transaction_histories');
    }
}
