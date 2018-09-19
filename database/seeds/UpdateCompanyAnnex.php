<?php

/**
 * Created by PhpStorm.
 * User: liqihai
 * Date: 16/9/19
 * Time: 下午3:31
 */

class UpdateCompanyAnnex extends BaseSeeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    protected function task()
    {
       \DB::update("
       update company_annexes set ios_version='0.0.0' , android_version='0.0.0';
       ");
    }
}
