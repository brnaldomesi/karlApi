<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTableBills extends Migration
{
    /**
     * Run the migrations.
     * company_id 公司id
     * booking_id booking id
     * settle_fee 结算费用
     * @return void
     */
    public function up()
    {
        Schema::create('bills', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->unsignedInteger('company_id');
            $table->unsignedInteger('booking_id');
            $table->decimal('order_actual_fee',8,2);
            $table->decimal('settle_fee',8,2);
            $table->decimal('platform_income',8,2);
            $table->timestamp('settle_time')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('bills');
    }
}
