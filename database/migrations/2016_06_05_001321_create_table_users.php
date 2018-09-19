<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTableUsers extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->unsignedInteger('company_id');
            $table->string('first_name',255);
            $table->string('last_name',255);
            $table->string('username',255);
            $table->string('mobile',255);
            $table->string('email',255);
            $table->string('password',255);
            $table->string('address',255);
            $table->string('address_number',255);
            $table->string('address_code_postal',255);
            $table->string('avatar_url',1024);
            $table->unsignedInteger('gender')->default(2);
            $table->string('token',255)->nullable();
            $table->timestamp('token_invalid_time')->nullable();
            $table->string('device_token',1024)->nullable();
            $table->unsignedInteger('timemode')->default(0);
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
        Schema::drop('users');
    }
}
