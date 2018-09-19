<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddColumnForLnProvide extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('ln_provide_records', function (Blueprint $table) {
            //
            $table->tinyInteger('secret');
            $table->tinyInteger('provide');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('ln_provide_records', function (Blueprint $table) {
            //
            $table->dropColumn(['secret',"provide"]);
        });
    }
}
