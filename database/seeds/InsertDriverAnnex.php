<?php

/**
 * Created by PhpStorm.
 * User: liqihai
 * Date: 16/9/19
 * Time: 下午3:31
 */

class InsertDriverAnnex extends BaseSeeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    protected function task()
    {
       \DB::insert("
       INSERT INTO company_annexes (company_id, ios_app, android_app, ios_id, ios_version, android_version, pkg_name)
VALUES (0,'https://itunes.apple.com/us/app/karl-driver-app/id1148069917','https://play.google.com/store/apps/details?id=com.inov.a4cdriver','1148069917','1.1.7','1.1.7','com.inov.a4cdriver');
       ");
    }
}
