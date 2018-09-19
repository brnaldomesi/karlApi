<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangeTableOp extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('offer_prices', function (Blueprint $table) {
            //
            DB::statement("ALTER TABLE acdp_lumen.offer_prices MODIFY invl_start DOUBLE NOT NULL;");
            DB::statement("ALTER TABLE acdp_lumen.offer_prices MODIFY invl_end DOUBLE NOT NULL;");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('offer_prices', function (Blueprint $table) {
            //
        });
    }
}
