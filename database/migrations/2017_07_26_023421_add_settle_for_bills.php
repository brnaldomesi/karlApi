<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddSettleForBills extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('bills', function (Blueprint $table) {
            //
            $table->integer('exe_com_id')->after('ccy');
            $table->integer('own_com_id')->after('ccy');
            $table->string('exe_trans_id')->after('an_fee');
            $table->string('own_trans_id')->after('an_fee');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('bills', function (Blueprint $table) {
            //
            $table->dropColumn([
                'exe_com_id',
                'own_com_id',
                'exe_trans_id',
                'own_trans_id'
            ]);
        });
    }
}
