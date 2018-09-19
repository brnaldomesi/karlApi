<?php
use Illuminate\Support\Facades\DB;

/**
 * Created by PhpStorm.
 * User: liqihai
 * Date: 16/9/19
 * Time: 下午3:31
 */

class ResetCreditCardInfo extends BaseSeeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    protected function task()
    {
        DB::update("delete from credit_cards");

        DB::update("delete from stripe_customers");

        DB::update("delete from company_pay_methods");

        DB::update("update companies set stripe_acct_id=''");
    }
}
