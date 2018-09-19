<?php

namespace App\Jobs;

use App\Constants;
use App\Model\Booking;
use App\Model\Calendar;
use App\Model\CalendarEvent;
use App\Model\Order;
use App\QueueName;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Lang;

class BookingCancelCustomQuoteJob extends Job
{
    protected $booking_id;

    /**
     * Create a new job instance.
     *
     * @param  $booking_id
     */
    public function __construct($booking_id)
    {
        $this->booking_id = $booking_id;
        $this->onQueue(QueueName::SystemCancelCustomQuote);
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function work()
    {
        //1.判断订单是否已经确认
        $order = Order::where('booking_id', $this->booking_id)
            ->where('order_state', Order::ORDER_STATE_WAIT_DETERMINE)
            ->first();
        if (empty($order)) {
            return;
        }
        //2.未确认取消,修改order
        //3.释放司机、汽车
        try {
            DB::transaction(function () {
                $booking = Booking::where('id', $this->booking_id)
                    ->leftJoin("customers",'bookings.customer_id',"=","customers.id")
                    ->leftJoin("users",'customers.user_id',"=","users.id")
                    ->select(
                        "bookings.driver_id",
                        "bookings.id",
                        "bookings.car_id",
                        "bookings.customer_id",
                        "users.lang"
                    )
                    ->first();
                if (empty($booking)) {
                    return;
                }
                $driverEvent = CalendarEvent::where([
                    ['re_owner_id', $booking->driver_id],
                    ['re_type', Calendar::DRIVER_TYPE],
                    ['creator_id', $booking->id],
                    ['creator_TYPE', CalendarEvent::CREATOR_TYPE_BOOKING]
                ])
                    ->first();
                $driverEvent->enable = 0;
                $driverEvent->save();
                $carEvent = CalendarEvent::where([
                    ['re_owner_id', $booking->car_id],
                    ['re_type', Calendar::CAR_TYPE],
                    ['creator_id', $booking->id],
                    ['creator_TYPE', CalendarEvent::CREATOR_TYPE_BOOKING]
                ])->first();
                $carEvent->enable = 0;
                $carEvent->save();

                $order = Order::where('booking_id', $booking->id)->first();
                $order->order_state = Order::ORDER_STATE_TIMES_UP_CANCEL;
                $order->save();

                app("translator")->setLocale($booking->lang);
                $msg = Lang::get("push_message.tripTimeOut");
                $this->pushNotification($booking->customer_id,Constants::CUSTOMER_PUSH,$msg);
            });

        } catch (\Exception $ex) {
        }

    }
}
