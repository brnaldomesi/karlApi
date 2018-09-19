<?php

namespace App\Jobs;

use App\Constants;
use App\Model\Customer;
use App\Model\Driver;
use App\QueueName;
use Illuminate\Support\Facades\Lang;

class PushBookingUpdateJob extends Job
{
    private $customerId = null;
    private $oldDriverId = null;
    private $newDriverId = null;

    /**
     * PushBookingUpdateJob constructor.
     * @param $customerId
     * @param $oldDriverId
     * @param null $newDriverId
     */
    public function __construct($customerId , $oldDriverId, $newDriverId=null)
    {
        $this->customerId = $customerId;
        $this->oldDriverId = $oldDriverId;
        $this->newDriverId = $newDriverId;
        $this->onQueue(QueueName::PushBookingSuccess);
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function work()
    {
        $customer = Customer::leftJoin("users","customers.user_id","=","users.id")
            ->where("customers.id",$this->customerId)
            ->select(
                "customers.id",
                "users.lang"
            )->first();
        app('translator')->setLocale($customer->lang);
        $msg = Lang::get("push_message.clientCancelTrip");
        $this->pushNotification($this->customerId,Constants::CUSTOMER_PUSH,$msg);
        if($this->newDriverId == null || $this->newDriverId == $this->oldDriverId){
            $driver=Driver::leftJoin("users","drivers.user_id","=","users.id")
                ->where("drivers.id",$this->oldDriverId)
                ->select(
                    "drivers.id",
                    "users.lang"
                )->first();
            app('translator')->setLocale($driver->lang);
            $msg = Lang::get("push_message.driverUpdateTrip");
            $this->pushNotification($this->oldDriverId,Constants::DRIVER_PUSH,$msg);
        }else{
            $oldDriver=Driver::leftJoin("users","drivers.user_id","=","users.id")
                ->where("drivers.id",$this->oldDriverId)
                ->select(
                    "drivers.id",
                    "users.lang"
                )->first();
            app('translator')->setLocale($oldDriver->lang);
            $msg = Lang::get("push_message.tripCancel");
            $this->pushNotification($this->oldDriverId,Constants::DRIVER_PUSH,$msg);

            $newDriver=Driver::leftJoin("users","drivers.user_id","=","users.id")
                ->where("drivers.id",$this->newDriverId)
                ->select(
                    "drivers.id",
                    "users.lang"
                )->first();
            app('translator')->setLocale($newDriver->lang);
            $msg = Lang::get("push_message.driverUpdateTrip");
            $this->pushNotification($this->oldDriverId,Constants::DRIVER_PUSH,$msg);
        }
    }
}
