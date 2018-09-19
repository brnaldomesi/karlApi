<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateOnetimeCouponhistories extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //
        Schema::create('onetime_coupon_histories', function(Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->string('coupon_code');
            $table->integer('user_id');
            $table->integer('company_id');
            $table->timeStamp('used_date');
            $table->timestamps();
            // Schema declaration
            // Constraints declaration

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
        Schema::drop('onetime_coupon_histories');
    }
}
