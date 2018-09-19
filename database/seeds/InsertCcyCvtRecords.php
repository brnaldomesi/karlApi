<?php
use Illuminate\Support\Facades\DB;

/**
 * Created by PhpStorm.
 * User: liqihai
 * Date: 16/9/19
 * Time: 下午3:31
 */

class InsertCcyCvtRecords extends BaseSeeder
{
    /**
     * Run the database seeds.
     */
    protected function task()
    {
        \Illuminate\Support\Facades\Artisan::call('ccy:cvt');
    }
}