<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddUnitForOffers extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('offers', function (Blueprint $table) {
            $table->renameColumn("distance_min","mi_min");
            $table->renameColumn("distance_max","mi_max");
            $table->double("km_min")->nullable()->after("distance_max");
            $table->double("km_max")->nullable()->after("km_min");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('offers', function (Blueprint $table) {
            //
//            $table->integer("distance_min")->change();
            $table->dropColumn(["unit","km_min","km_max"]);
            $table->renameColumn("mi_min","distance_min");
            $table->renameColumn("mi_max","distance_max");
        });
    }
}
