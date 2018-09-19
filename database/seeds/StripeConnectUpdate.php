<?php
use App\Model\Offer;
use Illuminate\Support\Facades\DB;

/**
 * Created by PhpStorm.
 * User: liqihai
 * Date: 16/9/19
 * Time: 下午3:31
 */

class StripeConnectUpdate extends BaseSeeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    protected function task()
    {
        DB::transaction(function(){
            DB::update("update bills LEFT JOIN bookings on bills.booking_id=bookings.id set bills.own_com_id=bookings.company_id , bills.exe_com_id = bookings.exe_com_id");
            DB::delete("delete from credit_cards");
            DB::delete("delete from stripe_customers");
        });
    }
}
