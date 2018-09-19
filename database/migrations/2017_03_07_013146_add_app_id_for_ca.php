<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddAppIdForCa extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('company_annexes', function (Blueprint $table) {
            //
            $table->string("ios_id")->after("ios_app");
            $table->string("ios_version")->default('0.0.0')->after("ios_id");

            $table->string("pkg_name")->after("android_app");
            $table->string("android_version")->default('0.0.0')->after("pkg_name");

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('company_annexes', function (Blueprint $table) {
            //
            $table->dropColumn([
                "ios_id",
                "pkg_name",
                "ios_version",
                "android_version"
            ]);
        });
    }
}
