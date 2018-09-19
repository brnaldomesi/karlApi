<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTableReferrals extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('referrals', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->unsignedInteger('status');
            $table->string('name',255);
            $table->decimal('tva',8,2);
            $table->string('gmt',255);

            $table->string('address',255);
            $table->string('address_number',255);
            $table->string('address_code_postal',255);
            $table->double('lat');
            $table->double('lng');
            $table->string('domain',255);
            $table->string('img',255);
            $table->string('email',255);
            $table->string('phone1',255);
            $table->string('phone2',255);
            $table->string('admin_username',1024);
            $table->string('admin_password',1024);
            
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
        Schema::drop('referrals');
    }
}
