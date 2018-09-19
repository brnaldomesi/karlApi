<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddColumnAirportForOffer extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('offers', function (Blueprint $table) {
            //
            $table->tinyInteger('d_is_port')->default(0)->after('d_address');
            $table->double('d_port_price')->default(0)->after('d_is_port');
            $table->tinyInteger('a_is_port')->default(0)->after('a_address');
            $table->double('a_port_price')->default(0)->after('a_is_port');
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
            $table->dropColumn([
                'd_is_port',
                'd_port_price',
                'a_is_port',
                'a_port_price'
            ]);
        });
    }
}
