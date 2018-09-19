<?php

namespace App\Jobs;

use App\Constants;
use App\QueueName;

class PushDriverJob extends Job
{
    private $target_id;
    private $message;
    const DRIVER_QUEUE='driver-push';
    const CUSTOMER_QUEUE='customer-push';
    public function __construct($target_id,$message)
    {
        //
        $this->target_id = $target_id;
        $this->message = $message;
        $this->onQueue(QueueName::PushDriverMsg);

    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function work()
    {
        //
        $this->pushNotification($this->target_id,Constants::DRIVER_PUSH,$this->message);
    }
}
