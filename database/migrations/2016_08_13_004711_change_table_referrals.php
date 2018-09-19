<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangeTableReferrals extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('referrals', function (Blueprint $table) {
//            $table->string('tax_id')->nullable()->after('phone2');
//            $table->integer('company_id')->nullable()->after('tax_id');
//            $table->string('email_host')->nullable()->after('company_id');
//            $table->string('email_port')->nullable()->after('email_host');
//            $table->string('email_password')->nullable()->after('email_port');
//            $table->string('pay_id')->nullable()->after('email_password');
//            $table->string('auth_code')->nullable()->after('pay_id');
            $table->drop();
        });
    }

    /**
     * Reverse the migrations.
     * @return void
     */
    public function down()
    {
        Schema::table('referrals', function (Blueprint $table) {
            //
        });
    }
}
