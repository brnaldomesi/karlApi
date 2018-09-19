<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTableOptions extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('options', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->unsignedInteger('company_id');
            $table->string('title',255);
            $table->string('description',1024);
            $table->decimal('price',8,2);
            $table->enum('type',['NUMBER','CHECKBOX','RADIO','GROUP']);
            $table->unsignedInteger('parent_id');
            $table->unsignedInteger('add_max');
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
        Schema::drop('options');
    }
}
