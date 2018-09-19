<?php
use Illuminate\Support\Facades\DB;

/**
 * Created by PhpStorm.
 * User: liqihai
 * Date: 16/9/19
 * Time: 下午3:31
 */
class UpdateCarsRating extends BaseSeeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    protected function task()
    {
        DB::update("
UPDATE cars
  LEFT JOIN (SELECT
               count(*)               AS rating,
               car_id,
               sum(feedbacks.quality) AS quality
             FROM bookings
               LEFT JOIN orders ON orders.booking_id = bookings.id
               LEFT JOIN feedbacks ON orders.id = feedbacks.order_id
             GROUP BY bookings.car_id) a ON cars.id = a.car_id
SET cars.count_rating = ifnull(a.rating,0), cars.count_quality = ifnull(a.quality,0);");
    }
}
