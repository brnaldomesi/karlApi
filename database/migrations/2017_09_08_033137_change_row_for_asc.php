<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangeRowForAsc extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('sale_asst_companies', function (Blueprint $table) {
            //
            \DB::update("alter table sale_asst_companies modify column sale_id varchar(255);");
            \DB::update("alter table sale_asst_companies modify column asst_id varchar(255);");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('sale_asst_companies', function (Blueprint $table) {
            //
        });
    }
}
