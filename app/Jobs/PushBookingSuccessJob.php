<?php

namespace App\Jobs;

use App\Constants;
use App\Model\Customer;
use App\Model\Driver;
use App\QueueName;
use App\PushMsg;
use Illuminate\Support\Facades\Lang;

class PushBookingSuccessJob extends Job
{
    /**
     * Create a new job instance.
     *
     * @return void
     */
    private $customer_id ;
    private $driver_id ;
    public function __construct($customer_id,$driver_id)
    {
        //
        $this->customer_id = $customer_id;
        $this->driver_id = $driver_id;
        $this->onQueue(QueueName::PushBookingSuccess);
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function work()
    {
        $customer = Customer::leftjoin("users","customers.user_id","=","users.id")
            ->where("customers.id",$this->customer_id)
            ->select(
                "customers.id",
                "users.lang"
            )
            ->first();
        app("translator")->setLocale($customer->lang);
        $msg = Lang::get("push_message.clientNewTrip");
        $this->pushNotification($this->customer_id, Constants::CUSTOMER_PUSH, $msg);

        $driver = Driver::leftJoin("users","drivers.user_id","=","users.id")
            ->where("drivers.id",$this->driver_id)
            ->select(
                "drivers.id",
                "users.lang"
            )
            ->first();
        app("translator")->setLocale($driver->lang);
        $msg = Lang::get("push_message.driverNewTrip");
        $this->pushNotification($this->driver_id, Constants::DRIVER_PUSH, $msg);
    }
}
