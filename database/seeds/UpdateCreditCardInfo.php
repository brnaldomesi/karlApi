<?php
use Illuminate\Support\Facades\DB;

/**
 * Created by PhpStorm.
 * User: liqihai
 * Date: 16/9/19
 * Time: 下午3:31
 */

class UpdateCreditCardInfo extends BaseSeeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    protected function task()
    {
        DB::update("update credit_cards LEFT JOIN customers on credit_cards.owner_id=customers.id
  LEFT JOIN users on customers.user_id=users.id
    LEFT JOIN company_pay_methods on users.company_id= company_pay_methods.company_id
SET credit_cards.pay_method_id = company_pay_methods.id
WHERE left(credit_cards.card_token,5) ='CARD-' and company_pay_methods.pay_type=1;");

        DB::update("update credit_cards LEFT JOIN customers on credit_cards.owner_id=customers.id
  LEFT JOIN users on customers.user_id=users.id
    LEFT JOIN company_pay_methods on users.company_id= company_pay_methods.company_id
SET credit_cards.pay_method_id = company_pay_methods.id
WHERE left(credit_cards.card_token,5) ='card_' and company_pay_methods.pay_type=3;"
        );
    }
}
