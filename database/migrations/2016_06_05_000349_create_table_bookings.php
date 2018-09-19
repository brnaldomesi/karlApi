<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTableBookings extends Migration
{
    /**
     * Run the migrations.
     *
     * type
     * company_id
     *
     * preparation_time
     * appointed_at
     *
     * d_lat
     * d_lng
     * d_address
     *
     * a_lat
     * a_lng
     * a_address
     *
     * estimate_time
     * estimate_distance
     *
     * total_cost
     * base_cost
     * option_cost
     * 
     * offer_id
     * offer_data
     * car_id
     * car_data
     * driver_id
     * driver_data
     * option_data
     * tva
     * 
     * customer_id
     * pay_card_data
     * message
     * custom_auth_code
     */
    public function up()
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('id');
            
            $table->unsignedInteger('type');
            $table->unsignedInteger('company_id');

            $table->timestamp('preparation_time');
            $table->timestamp('appointed_at');

            $table->string('d_address',1024);
            $table->double('d_lat');
            $table->double('d_lng');

            $table->string('a_address',1024)->nullable();
            $table->double('a_lat')->nullable();
            $table->double('a_lng')->nullable();

            $table->unsignedInteger('estimate_time');
            $table->double('estimate_distance')->nullable();


            $table->decimal('total_cost',8,2);
            $table->decimal('base_cost',8,2);
            $table->decimal('option_cost',8,2)->default(0,0);



            $table->unsignedInteger('offer_id');
            $table->string('offer_data',1024);
            $table->unsignedInteger('car_id');
            $table->string('car_data',1024);
            $table->unsignedInteger('driver_id');
            $table->string('driver_data',1024);
            $table->string('option_data',1024)->default('[]');
            $table->decimal('tva',8,2)->default(0.0);

            $table->unsignedInteger('customer_id');
            $table->string('pay_card_data',1024);
            $table->string('message',1024)->default('');
            $table->string('custom_auth_code',32)->nullable();

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
        Schema::drop('bookings');
    }
}
