<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddColumCountForCustomer extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('customers', function (Blueprint $table) {
            //
            $table->dropColumn("mc_count");
            $table->integer("booking_total")->after("user_id");
            $table->double("cost_total")->after("booking_total");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('customers', function (Blueprint $table) {
            //
            $table->integer("mc_count")->after("user_id");
            $table->dropColumn(["booking_total","cost_total"]);
        });
    }
}
