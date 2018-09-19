<?php
use Illuminate\Support\Facades\DB;

/**
 * Created by PhpStorm.
 * User: liqihai
 * Date: 16/9/19
 * Time: 下午3:31
 */

class UpdateCarsBagsAndSeats extends BaseSeeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    protected function task()
    {
        DB::update("
UPDATE cars LEFT JOIN car_models on cars.car_model_id = car_models.id
SET cars.bags_max = car_models.bags_max , cars.seats_max = car_models.seats_max;");
    }
}
