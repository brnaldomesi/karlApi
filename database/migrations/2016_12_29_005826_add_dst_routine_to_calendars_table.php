<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddDstRoutineToCalendarsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('calendars', function (Blueprint $table) {
            $table->string('dst_routine', 1024)
                ->default('["000000000000000000000000000000000000000000000000","000000000000000000000000000000000000000000000000","000000000000000000000000000000000000000000000000","000000000000000000000000000000000000000000000000","000000000000000000000000000000000000000000000000","000000000000000000000000000000000000000000000000","000000000000000000000000000000000000000000000000"]')
                ->after('routine');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('calendars', function (Blueprint $table) {
            $table->dropColumn('dst_routine');
        });
    }
}
