<?php
use Illuminate\Support\Facades\DB;

/**
 * Created by PhpStorm.
 * User: liqihai
 * Date: 16/9/19
 * Time: 下午3:31
 */

class UpdateCarPreTime extends BaseSeeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */

    protected function task()
    {
        DB::update("update cars set pre_time=30");
        DB::update("update bookings set car_data=replace(car_data,'\"}','\",\"pre_time\":30}')");
    }
}