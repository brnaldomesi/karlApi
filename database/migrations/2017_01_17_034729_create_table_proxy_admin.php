<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTableProxyAdmin extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('proxy_admins', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('company_id');
            $table->string('username',255);
            $table->string('password',255);
            $table->string('token',255);
            $table->integer('creator_id');
            $table->timestamp('expire_time')->default(\DB::raw('CURRENT_TIMESTAMP'));
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('proxy_admins');
    }
}
