<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddNewRowTableCompanyApprovalRecordings extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('company_approval_recordings', function (Blueprint $table) {
            //

            $table->string('order_id')->after('approval_state');
            $table->string('pay_id')->after('order_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('company_approval_recordings', function (Blueprint $table) {
            //
        });
    }
}
