<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class DropColumnForTableBth extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('booking_transaction_histories', function (Blueprint $table) {
            //
            $table->renameColumn("pay1_auth_id","pay1_refund_id");
            $table->dropColumn(["capture_id","capture_amount","capture_success"]);
            $table->string("pay2_refund_id")->after("pay2_success");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('booking_transaction_histories', function (Blueprint $table) {
            //
        });
    }
}
