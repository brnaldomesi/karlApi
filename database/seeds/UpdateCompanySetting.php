<?php
use Illuminate\Support\Facades\DB;

/**
 * Created by PhpStorm.
 * User: liqihai
 * Date: 16/9/19
 * Time: 下午3:31
 */

class UpdateCompanySetting extends BaseSeeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    protected function task()
    {
        DB::insert("INSERT INTO company_an_settings (company_id) select companies.id from companies;");

        DB::update("UPDATE company_an_settings SET created_at=now(),updated_at=now();");
    }
}
