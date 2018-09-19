<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddMcForCs extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('company_settings', function (Blueprint $table) {
            //
            $table->string('mc_url')->default("");
            $table->string('mc_key')->default("");
            $table->string('mc_list')->default("");
            $table->string('mc_list_id')->default("");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('company_settings', function (Blueprint $table) {
            //
            $table->dropColumn('mc_url');
            $table->dropColumn('mc_key');
            $table->dropColumn('mc_list');
            $table->dropColumn('mc_list_id');
        });
    }
}
