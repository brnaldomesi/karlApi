<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCouponMigrationTable extends Migration
{

    public function up()
    {
        Schema::create('coupons', function(Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->integer('company_id');
            $table->string('code');
            $table->float('discount_amount');
            $table->tinyInteger('discount_type');
            $table->tinyInteger('is_onetime')->default(0);
            $table->string('title');
            $table->date('starting_date');
            $table->date('end_date');
            $table->tinyInteger('is_permanent')->default(0);
            $table->tinyInteger('turn_state');
            $table->timestamps();
            $table->softDeletes();
            // Schema declaration
            // Constraints declaration

        });
    }

    public function down()
    {
        Schema::drop('coupons');
    }
}
