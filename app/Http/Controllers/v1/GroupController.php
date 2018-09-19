<?php
/**
 * Created by PhpStorm.
 * User: lqh
 * Date: 2017/5/10
 * Time: 上午11:00
 */

namespace App\Http\Controllers\v1;


use App\ErrorCode;
use App\Jobs\SyncCustomerToGroup;
use App\Method\GroupCenter;
use App\Method\MethodAlgorithm;
use App\Model\Customer;
use App\Model\CustomerGroupBinders;
use App\Model\CustomerGroups;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Input;
use Pheanstalk\Exception;

class GroupController extends Controller
{
    public function getGroupSetting(Request $request)
    {
        $companyId = $request->user->company_id;
        $binder = $this->getOutBindSetting($companyId);
        if (empty($binder)) {
            return ErrorCode::successEmptyResult("");
        }
        return ErrorCode::success($binder);
    }


    public function removeGroupSetting(Request $request)
    {
        $companyId = $request->user->company_id;
        $binder = CustomerGroupBinders::where("company_id", $companyId)
            ->first();
        if (empty($binder)) {
            return ErrorCode::errorNotExist("bind");
        }
        DB::transaction(function () use ($companyId, $binder) {
            DB::delete("DELETE FROM customer_group_members WHERE group_id IN (SELECT id FROM customer_groups WHERE bind_id=" . $binder->id . ")");
            DB::delete("DELETE FROM customer_groups WHERE bind_id =" . $binder->id);
            $binder->delete();
        });
        return ErrorCode::success("success");
    }

    public function checkApiKeyAndGetLists()
    {
        $key = Input::get("key", null);
        $type = Input::get("type", CustomerGroupBinders::TYPE_MAIL_CHIMP);
        if (is_null($key)) {
            return ErrorCode::errorParam("key");
        }
        $lists = GroupCenter::getGroupList($key, $type);
        if (is_null($lists)) {
            return ErrorCode::errorOutGroupApiKey();
        }
        if (count($lists) == 0) {
            return ErrorCode::successEmptyResult("no lists");
        } else {
            return ErrorCode::success($lists);
        }
    }

    public function getOutGroupList(Request $request)
    {
        $companyId = $request->user->company_id;
        $bind = CustomerGroupBinders::where("company_id", $companyId)
            ->first();
        if (empty($bind)) {
            return ErrorCode::errorOutGroupNotSetApiKey();
        } else {
            $lists = GroupCenter::getGroupList($bind->outer_key, $bind->type);
            if (is_null($lists)) {
                return ErrorCode::errorOutGroupApiKey();
            }
            if (count($lists) == 0) {
                return ErrorCode::successEmptyResult("no lists");
            } else {
                return ErrorCode::success($lists);
            }
        }
    }


    public function getMemberBelong(Request $request)
    {
        $companyId = $request->user->company_id;
        $email = Input::get("email", null);
        if (is_null($email) || !MethodAlgorithm::emailRegex($email)) {
            return ErrorCode::errorParam("email");
        }
        $bind = CustomerGroupBinders::where("company_id", $companyId)
            ->first();
        if (empty($bind)) {
            return ErrorCode::errorOutGroupNotSetApiKey();
        }
        $lists = GroupCenter::getCustomerGroupInfo($bind->outer_key, $bind->type, $email);
        if (is_null($lists)) {
            return ErrorCode::errorOutGroupApiKey();
        }
        if (count($lists) == 0) {
            return ErrorCode::successEmptyResult("no lists");
        } else {
            return ErrorCode::success($lists);
        }
    }


    public function changeOutMemberList(Request $request)
    {
        $companyId = $request->user->company_id;
        $email = Input::get("email", null);
        $listId = Input::get("list_id", null);
        $change = Input::get("change", null);
        if (is_null($email) || !MethodAlgorithm::emailRegex($email)) {
            return ErrorCode::errorParam("email");
        }
        $customer = Customer::leftjoin("users", "customers.user_id", "=", "users.id")
            ->where("users.email", $email)
            ->where("users.company_id", $companyId)
            ->select(
                "customers.id",
                "users.first_name",
                "users.last_name"
            )
            ->first();
        if (empty($customer)) {
            return ErrorCode::errorNotExist("customer");
        }

        if (empty($listId)) {
            return ErrorCode::errorParam("list_id");
        }
        if (!is_numeric($change) ||
            ($change != 0 &&
                $change != 1)
        ) {
            return ErrorCode::errorParam("change");
        }
        $bind = CustomerGroupBinders::where("company_id", $companyId)
            ->first();
        if (empty($bind)) {
            return ErrorCode::errorOutGroupNotSetApiKey();
        }
        $result = GroupCenter::changeGroupMemberList($bind->outer_key, $bind->type, $email, $customer->first_name, $customer->last_name, $listId, $change);
        if ($result) {
            return ErrorCode::success("success");
        } else {
            return ErrorCode::errorOuterGroupSetting();
        }
    }


    public function addOuterGroup(Request $request)
    {
        $companyId = $request->user->company_id;
        $bind = CustomerGroupBinders::where("company_id", $companyId)
            ->first();
        if (!empty($bind)) {
            if ($bind->state == CustomerGroupBinders::STATE_FINISH) {
                return ErrorCode::errorAlreadyExist("bind");
            } else {
                DB::transaction(function () use ($companyId, $bind) {
                    DB::delete("DELETE FROM customer_group_members WHERE group_id IN (SELECT id FROM customer_groups WHERE bind_id=" . $bind->id . ")");
                    DB::delete("DELETE FROM customer_groups WHERE bind_id =" . $bind->id);
                    $bind->delete();
                });
            }
        }
        $outKey = Input::get("key", null);
        $type = Input::get("type", null);
        $sort = Input::get("sort", null);
        $groups = Input::get("groups", null);
        $groups = json_decode($groups, true);
        if (!is_numeric($type) ||
            $type != CustomerGroupBinders::TYPE_MAIL_CHIMP
        ) {
            return ErrorCode::errorParam("type");
        }

        if (empty($outKey)) {
            return ErrorCode::errorParam("key");
        }

        if (is_null($groups)) {
            return ErrorCode::errorParam("groups");
        }

        try {
            DB::transaction(function () use ($companyId, $type, $sort, $outKey, $groups) {
                $bind = CustomerGroupBinders::create([
                    "company_id" => $companyId,
                    "type" => $type,
                    "sort" => $sort,
                    "state" => CustomerGroupBinders::STATE_INIT,
                    "outer_key" => $outKey,
                ]);
                CustomerGroups::checkAndCreateGroups($groups, $bind->id, $companyId);
            });
            $this->dispatch(new SyncCustomerToGroup($companyId));
            return ErrorCode::success($this->getOutBindSetting($companyId));
        } catch (\Exception $ex) {
            return $ex->getMessage();
        }

    }


    private function getOutBindSetting($companyId)
    {
        $binder = CustomerGroupBinders::where("company_id", $companyId)
            ->first();
        if (empty($binder)) {
            return null;
        }
        $groups = CustomerGroups::leftjoin(
            DB::raw("(select count(*) as count,customer_group_members.group_id 
            from customer_group_members group by customer_group_members.group_id) as cgm"),
            "cgm.group_id", "=", "customer_groups.id")
            ->where("customer_groups.company_id", $companyId)
            ->where("customer_groups.bind_id", $binder->id)
            ->select(
                DB::raw("ifnull(cgm.count,0) as count"),
                "customer_groups.id",
                "customer_groups.company_id",
                "customer_groups.name",
                "customer_groups.type",
                "customer_groups.priority",
                "customer_groups.section_start",
                "customer_groups.section_end",
                "customer_groups.outer_id"
            )
            ->orderBy("customer_groups.priority", "asc")
            ->get();
        $binder->groups = $groups;
        return $binder;
    }


    public function deleteAndAddGroup(Request $request)
    {
        $companyId = $request->user->company_id;

        $outKey = Input::get("key", null);
        $type = Input::get("type", null);
        $sort = Input::get("sort", null);
        $groups = Input::get("groups", null);
        $groups = json_decode($groups, true);
        if (!is_numeric($type) ||
            $type != CustomerGroupBinders::TYPE_MAIL_CHIMP
        ) {
            return ErrorCode::errorParam("type");
        }

        if (empty($outKey)) {
            return ErrorCode::errorParam("key");
        }

        if (is_null($groups)) {
            return ErrorCode::errorParam("groups");
        }

        $binder = CustomerGroupBinders::where("company_id", $companyId)
            ->first();
        if (empty($binder)) {
            return ErrorCode::errorNotExist("bind");
        }

        try {
            DB::transaction(function () use ($companyId, $binder, $type, $sort, $outKey, $groups) {
                DB::delete("DELETE FROM customer_group_members WHERE group_id IN (SELECT id FROM customer_groups WHERE bind_id=" . $binder->id . ")");
                DB::delete("DELETE FROM customer_groups WHERE bind_id =" . $binder->id);
                $binder->delete();

                $binder = CustomerGroupBinders::create([
                    "company_id" => $companyId,
                    "type" => $type,
                    "sort" => $sort,
                    "state" => CustomerGroupBinders::STATE_INIT,
                    "outer_key" => $outKey,
                ]);
                CustomerGroups::checkAndCreateGroups($groups, $binder->id, $companyId);
            });
            $this->dispatch(new SyncCustomerToGroup($companyId));
            return ErrorCode::success($this->getOutBindSetting($companyId));
        } catch (\Exception $ex) {
            return $ex->getMessage();
        }
    }
}