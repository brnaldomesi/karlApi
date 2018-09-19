<?php

namespace App\Jobs;

//添加乘客到MailChimp
use App\Method\GroupCenter;
use App\Model\Customer;
use App\Model\CustomerGroupBinders;
use App\Model\CustomerGroupMembers;
use App\QueueName;

class UpdateCustomerGroupInfo extends Job
{
    /**
     * Create a new job instance.
     */

    private $email = null;
    private $companyId = null;

    public function __construct($email, $companyId)
    {
        //
        $this->email = $email;
        $this->companyId = $companyId;
        $this->onQueue(QueueName::ChkCustomerGroup);
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function work()
    {
        $customer = Customer::leftjoin("users", "customers.user_id", "=", "users.id")
            ->where("users.company_id", $this->companyId)
            ->where("users.email", $this->email)
            ->select(
                "users.email",
                "users.first_name",
                "users.last_name",
                "customers.booking_total",
                "customers.cost_total",
                "customers.id as customer_id"
            )
            ->first();

        if (empty($customer)) {
            return;
        }

        $bind = CustomerGroupBinders::leftjoin("customer_groups", "customer_group_binders.id", "=", "customer_groups.bind_id")
            ->where("customer_group_binders.company_id", $this->companyId)
            ->where(function ($query) use ($customer) {
                $query->where(
                    [
                        ["customer_groups.type", CustomerGroupBinders::SORT_RIDES_COUNT],
                        ["customer_groups.section_start", "<=", $customer->booking_total],
                        ["customer_groups.section_end", ">", $customer->booking_total]
                    ]
                )->orWhere([
                    ["customer_groups.type", CustomerGroupBinders::SORT_COST_TOTAL],
                    ["customer_groups.section_start", "<=", $customer->cost_total],
                    ["customer_groups.section_end", ">", $customer->cost_total]
                ]);
            })
            ->select(
                "customer_group_binders.outer_key",
                "customer_groups.type",
                "customer_groups.outer_id",
                "customer_groups.id as group_id"
            )
            ->orderBy("customer_groups.priority","desc")
            ->first();
        if (empty($bind)) {
            return;
        }
        CustomerGroupMembers::where("customer_id",$customer->customer_id)->delete();
        GroupCenter::addCustomerToGroup($bind->outer_key,$bind->type,$bind->outer_id,
            $bind->group_id,$customer->customer_id,
            $customer->email,$customer->first_name,$customer->last_name);
    }
}
