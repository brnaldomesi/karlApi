<?php
use Illuminate\Support\Facades\DB;

/**
 * Created by PhpStorm.
 * User: liqihai
 * Date: 16/9/19
 * Time: 下午3:31
 */

class UpdateBookingsDriverDataAddEmail extends BaseSeeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */

    protected function task()
    {
        DB::update("UPDATE bookings LEFT JOIN drivers ON drivers.id = bookings.driver_id LEFT JOIN users on users.id=drivers.user_id
SET bookings.driver_data = replace(bookings.driver_data,'\"}',concat('\",\"email\":\"',ifnull(users.email,''),'\"}'))
    WHERE locate('\"email\"',bookings.driver_data)=0;");
    }
}
