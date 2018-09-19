<?php
use Illuminate\Support\Facades\DB;

/**
 * Created by PhpStorm.
 * User: liqihai
 * Date: 16/9/19
 * Time: 下午3:31
 */
class UpdateDriversRating extends BaseSeeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    protected function task()
    {
        DB::update("UPDATE drivers
  LEFT JOIN (SELECT
               count(*)               AS rating,
               driver_id,
               sum(feedbacks.driving_ability) AS drive,
               sum(feedbacks.cleanliness) AS clean,
               sum(feedbacks.professionalism) AS profess,
               sum(feedbacks.appearance) AS appear
             FROM bookings
               LEFT JOIN orders ON orders.booking_id = bookings.id
               LEFT JOIN feedbacks ON orders.id = feedbacks.order_id
             GROUP BY bookings.driver_id) a ON drivers.id = a.driver_id
SET drivers.count_rating = ifnull(a.rating,0),
  drivers.count_profess = ifnull(a.profess,0),
  drivers.count_drive = ifnull(a.drive,0),
  drivers.count_clean = ifnull(a.clean,0),
  drivers.count_appear = ifnull(a.appear,0);");
    }
}
