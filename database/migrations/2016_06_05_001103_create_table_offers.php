<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTableOffers extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('offers', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('id');
            
            $table->string('name',255);
            $table->string('description',255);
            $table->unsignedInteger('company_id');
            $table->unsignedInteger('type');
            
            $table->double('preparation_time');
            $table->decimal('cost_min',8,2);
            $table->decimal('price',8,2);
            $table->decimal('tva',8,2);
            $table->unsignedInteger('calc_method');

            $table->double('d_lat');
            $table->double('d_lng');
            $table->double('d_radius');
            $table->string('d_address');

            $table->double('a_lat')->nullable();
            $table->double('a_lng')->nullable();
            $table->double('a_radius')->nullable();
            $table->string('a_address')->nullable();

            $table->double('distance_min');
            $table->double('distance_max');
            $table->unsignedInteger('duration_min');
            $table->unsignedInteger('duration_max');

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
        Schema::drop('offers');
    }
}
