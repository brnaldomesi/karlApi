<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddColumForLnAsk extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('ln_ask_records', function (Blueprint $table) {
            //
            $table->tinyInteger("secret");
            $table->tinyInteger("needed");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('ln_ask_records', function (Blueprint $table) {
            //
            $table->dropColumn(['secret',"needed"]);
        });
    }
}
