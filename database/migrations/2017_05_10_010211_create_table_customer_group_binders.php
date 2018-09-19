<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTableCustomerGroupBinders extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('customer_group_binders', function (Blueprint $table) {
            $table->increments('id');
            $table->integer("company_id");
            $table->integer("type");
            $table->integer("state");
            $table->integer("sort");
            $table->string("outer_key");
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
        Schema::drop('customer_group_binders');
    }
}
