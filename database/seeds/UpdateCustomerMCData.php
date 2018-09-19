<?php
use Illuminate\Support\Facades\DB;

/**
 * Created by PhpStorm.
 * User: liqihai
 * Date: 16/9/19
 * Time: 下午3:31
 */
class UpdateCustomerMCData extends BaseSeeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    protected function task()
    {
        DB::update("
        UPDATE customers set customers.mc_count=1 WHERE customers.user_id in
                                        (select users.id FROM users
                                          LEFT JOIN company_settings on company_settings.company_id = users.company_id
                                        WHERE company_settings.mc_key !='' and company_settings.mc_list_id !=''
                                        ) and customers.mc_count = 0;
        ");
    }
}
