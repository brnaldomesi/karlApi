<?php

namespace App\Console\Commands;


use DB;
use Illuminate\Console\Command;

class DeleteCompany extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = 'delete:company {company_id}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new Eloquent model class';

    public function handle()
    {
        $company_id = $this->argument('company_id');
        if (empty($company_id)) {
            return;
        }
        DB::transaction(function () use ($company_id) {

            DB::delete("DELETE FROM company_settings WHERE company_id ={$company_id} ;");
            DB::delete("DELETE FROM company_approval_recordings WHERE company_id={$company_id};");
            DB::delete("DELETE FROM company_an_settings WHERE company_id={$company_id};");
            DB::delete("DELETE FROM company_annexes WHERE company_id={$company_id};");
            DB::delete("DELETE FROM company_push_config WHERE company_id={$company_id};");
            DB::delete("DELETE FROM company_pay_methods WHERE company_id={$company_id};");
            DB::delete("DELETE FROM options WHERE company_id={$company_id};");


            DB::delete("DELETE FROM booking_transaction_histories WHERE booking_id = (SELECT id FROM bookings WHERE company_id={$company_id});");
            DB::delete("DELETE FROM booking_airlines WHERE booking_id IN (SELECT id FROM bookings WHERE company_id={$company_id});");

            DB::delete("DELETE FROM orders WHERE booking_id=(SELECT id FROM bookings WHERE company_id={$company_id});");
            DB::delete("DELETE FROM bookings WHERE company_id={$company_id};");
            DB::delete("DELETE FROM booking_day_statistics WHERE company_id={$company_id};");
            DB::delete("DELETE FROM booking_change_histories WHERE company_id={$company_id};");

            DB::delete("DELETE FROM calendars WHERE company_id={$company_id};");
            DB::delete("DELETE FROM calendar_events WHERE re_company_id={$company_id};");
            DB::delete("DELETE FROM calendar_recurring_days WHERE repeat_event_id IN (SELECT cre.id FROM calendar_recurring_events AS cre LEFT JOIN drivers ON cre.owner_id = drivers.id LEFT JOIN users ON users.id = drivers.user_id WHERE cre.owner_type=1 AND users.company_id={$company_id});");
            DB::delete("DELETE FROM calendar_recurring_events  WHERE calendar_recurring_events.owner_type=1 AND calendar_recurring_events.owner_id IN (SELECT drivers.id FROM drivers LEFT JOIN users ON users.id = drivers.user_id WHERE users.company_id={$company_id});");

            DB::delete("DELETE FROM calendar_recurring_days WHERE repeat_event_id IN (SELECT cre.id FROM calendar_recurring_events AS cre LEFT JOIN cars ON cre.owner_id = cars.id WHERE cre.owner_type=2 AND cars.company_id={$company_id});");
            DB::delete("DELETE FROM calendar_recurring_events  WHERE calendar_recurring_events.owner_type=2 AND calendar_recurring_events.owner_id IN (SELECT cars.id FROM cars WHERE cars.company_id={$company_id});");
            DB::delete("DELETE FROM offer_driver_cars WHERE offer_id in (select * from offers where company_id = {$company_id})");
            DB::delete("DELETE FROM offer_prices WHERE offer_id in (select * from offers where company_id = {$company_id})");
            DB::delete("DELETE FROM offers WHERE company_id={$company_id};");

            DB::delete("DELETE FROM customers WHERE user_id IN (SELECT id FROM users WHERE company_id={$company_id});");
            DB::delete("DELETE FROM drivers WHERE user_id IN (SELECT id FROM users WHERE company_id={$company_id});");
            DB::delete("DELETE FROM users WHERE company_id = {$company_id};");;
            DB::delete("DELETE FROM companies WHERE id = {$company_id};");;

        });
    }
}
