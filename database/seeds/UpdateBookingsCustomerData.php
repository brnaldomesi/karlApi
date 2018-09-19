<?php
use Illuminate\Support\Facades\DB;

/**
 * Created by PhpStorm.
 * User: liqihai
 * Date: 16/9/19
 * Time: 下午3:31
 */

class UpdateBookingsCustomerData extends BaseSeeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */

    protected function task()
    {
        DB::update("UPDATE bookings
SET bookings.customer_data =
(select concat('{\"first_name\":\"',users.first_name,'\",
                  \"last_name\":\"',users.last_name,'\",
                  \"mobile\":\"',users.mobile,'\",
                  \"email\":\"',users.email,'\",
                  \"gender\":\"',users.gender,'\",
                  \"avatar_url\":\"',".\App\Method\UrlSpell::getUrlSpell()->getSpellAvatarInDB("users.updated_at","users.avatar_url","customers.id","",\App\Method\UrlSpell::companyCustomerType).",'\"}')
 from customers LEFT JOIN users on customers.user_id=users.id WHERE customers.id=bookings.customer_id)");
    }
}
