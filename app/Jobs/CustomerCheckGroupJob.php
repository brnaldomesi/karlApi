<?php

namespace App\Jobs;

use App\Method\GroupCenter;
use App\Model\Customer;
use App\Model\CustomerGroupBinders;
use App\Model\CustomerGroupMembers;
use App\Model\CustomerGroups;
use App\QueueName;

class CustomerCheckGroupJob extends Job
{
    /**
     * Create a new job instance.
     */
    private $customerId;

    public function __construct($customerId)
    {
        //
        $this->customerId = $customerId;
        $this->onQueue(QueueName::ChkCustomerGroup);
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function work()
    {
        //
        $customer = Customer::leftjoin("users", "customers.user_id","=", "users.id")
            ->where("customers.id", $this->customerId)
            ->select(
                "customers.booking_total",
                "customers.cost_total",
                "users.email",
                "users.first_name",
                "users.last_name"
            )
            ->first();
        $group = CustomerGroupMembers::leftjoin("customer_groups", "customer_group_members.group_id", "=", "customer_groups.id")
            ->leftjoin("customer_group_binders", "customer_groups.bind_id", "=", "customer_group_binders.id")
            ->where("customer_group_members.customer_id", $this->customerId)
            ->select(
                "customer_group_members.customer_id",
                "customer_group_members.customer_id_code",
                "customer_groups.id",
                "customer_groups.outer_id",
                "customer_groups.section_start",
                "customer_groups.section_end",
                "customer_group_binders.id as bind_id",
                "customer_group_binders.type",
                "customer_group_binders.sort",
                "customer_group_binders.outer_key"
            )
            ->first();
        if (empty($customer) || empty($group)) {
            return;
        }
        if ($group->sort == CustomerGroupBinders::SORT_RIDES_COUNT) {
            $update = ($customer->booking_total >= $group->section_start
                && $customer->booking_total < $group->section_end);
            $query = $customer->booking_total;
        } else if ($group->sort == CustomerGroupBinders::SORT_COST_TOTAL) {
            $update = ($customer->cost_total >= $group->section_start
                && $customer->cost_total < $group->section_end);
            $query = $customer->cost_total;
        } else {
            return;
        }

        if (!$update) {
            //移入新组
            $newGroup = CustomerGroups::where("section_start", "<=", $query)
                ->where("section_end", ">", $query)
                ->where("bind_id", $group->bind_id)
                ->orderBy('priority',"desc")
                ->first();
            if (!empty($newGroup)) {
                GroupCenter::addCustomerToGroup($group->outer_key, $group->type, $newGroup->outer_id,
                    $newGroup->id,$this->customerId,
                    $customer->email, $customer->first_name, $customer->last_name);
                //移出现有组
                GroupCenter::removeCustomerFromGroup($group->outer_key, $group->type, $group->outer_id,$group->id,$group->customer_id ,$group->customer_id_code);
            }
        }
    }
}
