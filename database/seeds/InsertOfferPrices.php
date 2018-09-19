<?php
use Illuminate\Support\Facades\DB;

/**
 * Created by PhpStorm.
 * User: liqihai
 * Date: 16/9/19
 * Time: 下午3:31
 */

class InsertOfferPrices extends BaseSeeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    protected function task()
    {
        DB::update("update offers set calc_method=1  WHERE type=1 and calc_method=2");
        DB::insert(
            "INSERT INTO offer_prices (offer_id,company_id, calc_method, invl_start, invl_end, price)
  SELECT
    id,
    company_id,
    calc_method,
    CASE WHEN type = 1
      THEN distance_min
      ELSE duration_min
    END AS invl_start,
    CASE WHEN type = 1
      THEN distance_max
      ELSE duration_max
    END AS invl_end,
    price
  FROM offers;"
        );
    }
}