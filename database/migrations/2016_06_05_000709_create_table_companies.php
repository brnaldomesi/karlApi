<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTableCompanies extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('companies', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->string('name',255);
            $table->string('domain',255);
            $table->string('img',1024);

            $table->string('gmt',255);
            $table->string('address',255);
            $table->string('address_number',255);
            $table->string('address_code_postal',255);
            $table->double('lat');
            $table->double('lng');

            $table->string('email',255);
            $table->string('phone1',255);
            $table->string('phone2',255);

            $table->decimal('tva',8,2);
            $table->string('trade_register_date',255);
            $table->string('trade_register_number',255);
            $table->string('tax_id',255);
            $table->string('commercial_license',255);

            $table->string('email_host',255);
            $table->string('email_port',255);
            $table->string('email_password',255);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('companies');
    }
}
