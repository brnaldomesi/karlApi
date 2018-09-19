<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTableCustomerGroupMembers extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */

    public function up()
    {
        Schema::create('customer_group_members', function (Blueprint $table) {
            $table->increments('id');
            $table->integer("group_id");
            $table->integer("customer_id");
            $table->string("customer_id_code")->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('customer_group_members');
    }
}
