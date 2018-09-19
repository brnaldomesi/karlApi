<?php

namespace App\Console\Commands;

use App\PushMsg;
use App\Jobs\PushCustomerJob;
use App\Jobs\PushDriverJob;
use App\Model\Booking;
use Illuminate\Support\Facades\DB;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Lang;

class OrderPushCheck extends Command
{

    /**
     * 预定推送,
     * 1.乘客在24小时内有订单即将执行,
     * 2.司机和乘客在1小时内有订单即将执行,
     */
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'order:push';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'check recent trip will begin';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        //
        $now = time();
        //向前取5分,向后取5分
        $hour24 = $now+24*3600;
        $start_time = $hour24-5*60;
        $end_time = $hour24+5*60;
        $customers = Booking::leftjoin('customers','customers.id','=','bookings.customer_id')
            ->leftjoin('users','users.id','=','customers.user_id')
            ->whereRaw('unix_timestamp(bookings.appointed_at)>'.$start_time)
            ->whereRaw('unix_timestamp(bookings.appointed_at)<='.$end_time)
            ->select('bookings.customer_id','users.last_name',
                DB::raw('case when users.gender=1 then \'Ms.\' ELSE \'Mr.\' END as gender'),
            "users.lang"
            )
            ->get();
        foreach ($customers as $customer) {
            app("translator")->setLocale($customer->lang);
            $msg = Lang::get("push_message.tripStart24h",["name"=>$customer->gender.' '.$customer->last_name]);
            dispatch(new PushCustomerJob($customer->customer_id,$msg));
        }
        $hour1 = $now+3600;
        $start_time = $hour1 -5*60;
        $end_time = $hour1 +5*60;
        $customers = Booking::leftjoin('customers','customers.id','=','bookings.customer_id')
            ->leftjoin('users','users.id','=','customers.user_id')
            ->whereRaw('unix_timestamp(bookings.appointed_at)>'.$start_time)
            ->whereRaw('unix_timestamp(bookings.appointed_at)<='.$end_time)
            ->select('bookings.customer_id','users.last_name',
                DB::raw('case when users.gender=1 then \'Ms.\' ELSE \'Mr.\' END as gender'),
                "users.lang"
            )
            ->get();
        foreach ($customers as $customer) {
            app("translator")->setLocale($customer->lang);
            $msg = Lang::get("push_message.tripStart1h",["name"=>$customer->gender.' '.$customer->last_name]);
            dispatch(new PushCustomerJob($customer->customer_id,$msg));
        }

        $drivers = Booking::leftJoin("drivers","bookings.driver_id","=","drivers.id")
            ->leftJoin("users","drivers.user_id","=","users.id")
            ->whereRaw('unix_timestamp(bookings.appointed_at)>'.$start_time)
            ->whereRaw('unix_timestamp(bookings.appointed_at)<='.$end_time)
            ->select('bookings.driver_id as d_id', 'bookings.customer_data' , "users.lang" )
            ->get();
        foreach ($drivers as $driver) {
            $customer = json_decode($driver->customer_data);
            app("translator")->setLocale($driver->lang);
            $msg = Lang::get("push_message.driver1HourNotice",["name"=>$customer->first_name.' '.$customer->last_name]);
            dispatch(new PushDriverJob($driver->d_id, $msg));
        }
    }
}
