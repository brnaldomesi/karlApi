<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTableStripeCustomers extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('stripe_customers', function (Blueprint $table) {
            $table->increments('id');
            $table->string(\App\Model\StripeCustomer::CUSTOMER_ID);
            $table->string(\App\Model\StripeCustomer::STRIPE_CUSTOMER_ID);
        });
    }

    /**
     * Reverse the migrations.
     * @return void
     */
    public function down()
    {
        Schema::drop('stripe_customers');
    }
}
