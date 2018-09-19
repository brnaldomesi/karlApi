<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTableComSetRecords extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('com_set_records', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('company_id');
            $table->integer('admin_id');
            $table->integer('user_id');
            $table->string('user_name',1024);
            $table->tinyInteger('type');
            $table->tinyInteger('change_type');
            $table->text('from_info');
            $table->text('to_info');
            $table->timestamp('change_time');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('com_set_records');
    }
}
