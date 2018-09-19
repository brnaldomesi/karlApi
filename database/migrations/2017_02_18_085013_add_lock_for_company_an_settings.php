<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddLockForCompanyAnSettings extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('company_an_settings', function (Blueprint $table) {
            //
            $table->tinyInteger('locked')->default(1)->after('company_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('company_an_settings', function (Blueprint $table) {
            //
            $table->dropColumn('locked');
        });
    }
}
