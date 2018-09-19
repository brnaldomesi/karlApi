<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddRadiumUnitToComAnSettings extends Migration
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
            $table->integer("unit")->default(1)->after('locked');
            $table->integer("radius")->after("unit");

        });

        DB::update("update company_an_settings LEFT JOIN company_settings on company_settings.company_id=company_an_settings.company_id
set company_an_settings.unit=company_settings.distance_unit ,
  company_an_settings.radius = 20; ");
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
            $table->dropColumn(['unit',"radius"]);
        });
    }
}
