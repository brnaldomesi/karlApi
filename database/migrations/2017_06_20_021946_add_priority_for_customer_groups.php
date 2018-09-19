<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddPriorityForCustomerGroups extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('customer_groups', function (Blueprint $table) {
            //
            $table->integer("type")->default(1)->after("id");
            $table->integer("priority")->default(1)->after("type");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('customer_groups', function (Blueprint $table) {
            //
            $table->dropColumn(['type',"priority"]);
        });
    }
}
