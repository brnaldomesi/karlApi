<?php
use Illuminate\Support\Facades\DB;

/**
 * Created by PhpStorm.
 * User: liqihai
 * Date: 16/9/19
 * Time: 下午3:31
 */

class InsertCompanySetting extends BaseSeeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    protected function task()
    {
        DB::insert(
            'insert into company_settings (company_id,hide_driver_fee,settle_type) SELECT id,0,2 FROM companies'
        );
    }
}