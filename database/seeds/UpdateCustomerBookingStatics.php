<?php
use Illuminate\Support\Facades\DB;

/**
 * Created by PhpStorm.
 * User: liqihai
 * Date: 16/9/19
 * Time: 下午3:31
 */

class UpdateCustomerBookingStatics extends BaseSeeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    protected function task()
    {
        DB::update("
        UPDATE  customers
  LEFT JOIN (SELECT bookings.customer_id,count(*) as count , sum(bth.pay1_amount-bth.pay1_refund_amount+bth.pay2_amount-bth.pay2_refund_amount) as cost
             FROM booking_transaction_histories AS bth LEFT JOIN bookings ON bth.booking_id=bookings.id GROUP BY bookings.customer_id) as b ON b.customer_id=customers.id
SET customers.cost_total =  b.cost,
  customers.booking_total = b.count
WHERE customers.id =b.customer_id;
        ");
    }
}
