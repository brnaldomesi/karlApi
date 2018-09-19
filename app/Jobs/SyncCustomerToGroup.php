<?php

namespace App\Jobs;
//添加乘客到MailChimp
use App\Method\GroupCenter;
use App\Method\PushCenter;
use App\Model\Customer;
use App\Model\CustomerGroupBinders;
use App\Model\CustomerGroups;
use App\QueueName;

class SyncCustomerToGroup extends Job
{
    /**
     * Create a new job instance.
     */

    private $companyId = null;

    public function __construct($companyId)
    {
        //
        $this->companyId = $companyId;
        $this->onQueue(QueueName::SyncCustomerToGroup);
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function work()
    {
        try{
            $bind = CustomerGroupBinders::where("company_id",$this->companyId)
                ->first();
            if(empty($bind)){
                return;
            }
            $outLists = GroupCenter::getGroupList($bind->outer_key,$bind->type);
            if(is_null($outLists)){
                throw new \Exception("error in get out list");
            }

            $groups = CustomerGroups::where("company_id",$this->companyId)
                ->where("bind_id",$bind->id)
                ->get();
            if(count($outLists) <count($groups)){
                throw new \Exception("error in match list count");
            }

            $sort = $bind->sort;
            $key = $bind->outer_key;
            $type = $bind->type;

//            \Log::info(json_encode($outLists));
            $listIds =[];
            for ($i=0 ; $i<count($outLists);  $i++) {
                $listIds[$i] = $outLists[$i]['id'];
            }

            foreach ($groups as $group) {
                if(!in_array($group->outer_id,$listIds)){
                   throw new \Exception("error in match list id");
                }
            }

            foreach ($groups as $group) {
                $customers = Customer::leftjoin("users","users.id","=","customers.user_id")
                    ->where("users.company_id",$this->companyId)
                    ->where(function ($query) use($group,$sort){
                        if($group->type == CustomerGroupBinders::SORT_RIDES_COUNT){
                            $query->where("customers.booking_total",">=",$group->section_start)
                                ->where("customers.booking_total","<",$group->section_end);
                        }else if($group->type == CustomerGroupBinders::SORT_COST_TOTAL){
                            $query->where("customers.cost_total",">=",$group->section_start)
                                ->where("customers.cost_total","<",$group->section_end);
                        }
                    })
                    ->select(
                        "users.email",
                        "users.first_name",
                        "users.last_name",
                        "customers.id as customer_id",
                        "customers.booking_total",
                        "customers.cost_total"
                    )
                    ->get();
//                \Log::info($customers);
                foreach ($customers as $customer) {
                    GroupCenter::addCustomerToGroup(
                        $key,$type,
                        $group->outer_id,
                        $group->id,
                        $customer->customer_id,
                        $customer->email,
                        $customer->first_name,
                        $customer->last_name);
                }
            }
            CustomerGroupBinders::where("company_id",$this->companyId)->update(["state"=>CustomerGroupBinders::STATE_FINISH]);
            PushCenter::initInstance()->sendAdminNotice('groupSycTitle',"groupSyc",$this->companyId,"https://mailchimp.com/");
        }catch(\Exception $ex){
            CustomerGroupBinders::where("company_id",$this->companyId)->update(["state"=>CustomerGroupBinders::STATE_FAIL]);
            PushCenter::initInstance()->sendAdminNotice('groupSycFieldTitle',"groupSycField",$this->companyId,$_SERVER["dashboard_url"]);
        }
    }
}
