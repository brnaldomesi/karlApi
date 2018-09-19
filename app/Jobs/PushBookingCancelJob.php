<?php

namespace App\Jobs;

use App\Constants;
use App\Model\Booking;
use App\QueueName;
use App\PushMsg;
use Illuminate\Support\Facades\Lang;

class PushBookingCancelJob extends Job
{
    /**
     * Create a new job instance.
     *
     * @return void
     */
    private $booking_id;
    public function __construct($booking_id)
    {
        //
        $this->booking_id = $booking_id;
        $this->onQueue(QueueName::PushBookingCancel);
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function work()
    {
        $booking = Booking::where('bookings.id',$this->booking_id)
            ->leftJoin("customers",'bookings.customer_id',"=","customers.id")
            ->leftJoin("users as cuser",'customers.user_id',"=","cuser.id")
            ->leftJoin("drivers",'bookings.driver_id',"=","drivers.id")
            ->leftJoin("users as duser",'drivers.user_id',"=","duser.id")
            ->select(
                "bookings.customer_id",
                "bookings.driver_id",
                \DB::raw("ifnull(cuser.lang,'en') as clang"),
                \DB::raw("ifnull(duser.lang,'en') as dlang")
            )
            ->first();
        if(empty($booking)){
            return;
        }
        app('translator')->setLocale($booking->clang);
        $msg = Lang::get("push_message.tripCancel");
        $this->pushNotification($booking->customer_id, Constants::CUSTOMER_PUSH, $msg);
        app('translator')->setLocale($booking->dlang);
        $msg = Lang::get("push_message.tripCancel");
        $this->pushNotification($booking->driver_id, Constants::DRIVER_PUSH, $msg);
    }
}
