<?php

namespace App\Jobs;

use App\Method\PaymentAlgorithm;
use App\Model\CreditCard;
use App\QueueName;

class CustomerRemoveAllCreditCardJob extends Job
{
    private $customerId;
    private $companyId;
    /**
     * Create a new job instance.
     * @param $companyId
     * @param $customerId
     */
    public function __construct($companyId,$customerId)
    {
        //
        $this->companyId = $companyId;
        $this->customerId = $customerId;
        $this->onQueue(QueueName::CustomerRemoveAllCreditCard);
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function work()
    {
        //
        PaymentAlgorithm::getPayment()->removeCustomerAllCard($this->companyId,$this->customerId);
        CreditCard::where('owner_id',$this->customerId)->where("type",CreditCard::TYPE_CUSTOMER)->delete();
    }
}
