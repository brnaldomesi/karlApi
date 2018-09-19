<?php

namespace App\Http\Controllers\v1;

use App\ErrorCode;
use App\Jobs\SyncCustomerToGroup;
use App\Model\CompanySetting;
use App\Model\Customer;
use DB;
use DrewM\MailChimp\MailChimp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Input;

class MailChimpController extends Controller
{
    public function getCompanyMailChimpSetting(Request $request)
    {
        $companyId = $request->user->company_id;
        $mc = CompanySetting::where('company_id', $companyId)
            ->select("mc_key", "mc_list_id")->first();
        return ErrorCode::success($mc);
    }

    public function updateCompanyMailChimpSetting(Request $request)
    {
        $companyId = $request->user->company_id;
        $key = Input::get("key", "");
        $listId = Input::get("list_id", "");

        if (
            !empty($key) &&
            !empty($listId)
        ) {
            $passed = false;
            try {
                $MailChimp = new MailChimp($key);
                $lists = $MailChimp->get('lists');
                if (!empty($lists) &&
                    isset($lists['lists']) &&
                    is_array($lists['lists'])
                ) {
                    foreach ($lists['lists'] as $list) {
                        if ($list['id'] == $listId) {
                            $passed = true;
                            break;
                        }
                    }
                }
            } catch (\Exception $ex) {
            }
            if ($passed) {
                CompanySetting::where('company_id', $companyId)
                    ->update(["mc_key" => $key, "mc_list_id" => $listId]);
//                $this->dispatch(new SyncCustomerToMC($companyId));
                return ErrorCode::success('success');
            } else {
                return ErrorCode::errorOuterGroupSetting();
            }
        } else {
            $key = "";
            $listId = "";
            CompanySetting::where('company_id', $companyId)
                ->update(["mc_key" => $key, "mc_list_id" => $listId]);
            DB::update("UPDATE customers set customers.mc_count=0 WHERE customers.user_id in
                                        (select users.id FROM users
                                        WHERE users.company_id = ".$companyId."
                                        ) ; ");
            return ErrorCode::success('success');
        }
    }

    public function checkApiKeyAndGetLists($key)
    {
        return $this->getList($key);
    }

    public function getChimpList(Request $request)
    {
        $companyId = $request->user->company_id;
        $key = CompanySetting::where("company_id", $companyId)
            ->whereNotNull("mc_key")
            ->select("mc_key")->first();
        if (empty($key) || empty($key->mc_key)) {
            return ErrorCode::errorOutGroupNotSetApiKey();
        } else {
            return $this->getList($key->mc_key);
        }
    }

    public function addClientToList()
    {

    }


    private function getList($key)
    {
        $MailChimp = new MailChimp($key);
        $lists = $MailChimp->get('lists');
        if (isset($lists['status']) && $lists['status'] == 401) {
            return ErrorCode::errorOutGroupApiKey();
        } else {
            if (!$lists) {
                return ErrorCode::successEmptyResult("no lists");
            } else {
                return ErrorCode::success($lists);
            }
        }
    }

    public function getEmailMemberBelong(Request $request)
    {
        $companyId = $request->user->company_id;
        $email = Input::get("email", null);
        $key = CompanySetting::where("company_id", $companyId)
            ->whereNotNull("mc_key")
            ->select("mc_key")->first();
        if (empty($key) || empty($key->mc_key)) {
            return ErrorCode::errorOutGroupNotSetApiKey();
        }
        $mailChimp = new MailChimp($key->mc_key);
        $memberList = $mailChimp->get("lists");

        $mcList = array();
        foreach ($memberList['lists'] as $list) {
            $mcList[$list['id']] =
                [
                    "id" => $list['id'],
                    "name" => $list["name"],
                    "checked" => 0
                ];
        }
        if (empty($mcList)) {
            return ErrorCode::successEmptyResult("");
        }
        $member = $mailChimp->get("search-members", ["query" => $email]);
        $count = $member['exact_matches']['total_items'];
        DB::update("
            UPDATE customers
SET mc_count = ".$count."
WHERE user_id = (select id FROM users where company_id='".$companyId."' and email='".$email."'); 
        ");
        foreach ($member['exact_matches']['members'] as $list) {
            $mcList[$list['list_id']]["checked"] = 1;
        }
        if (empty($mcList)) {
            return ErrorCode::successEmptyResult("");
        }
        return ErrorCode::success(array_values($mcList));
    }

    public function changeMCMemberList(Request $request)
    {
        $companyId = $request->user->company_id;
        $email = Input::get("email",null);
        $listId = Input::get("list_id",null);
        $change = Input::get("change",null);
        if(empty($email)){
            return ErrorCode::errorParam("email");
        }
        $customer = Customer::leftjoin("users","customers.user_id","=","users.id")
            ->where("users.email",$email)
            ->where("users.company_id",$companyId)
            ->select("customers.id","customers.mc_count")
            ->first();
        if(empty($customer)){
            return ErrorCode::errorNotExist("customer");
        }

        if(empty($listId)){
            return ErrorCode::errorParam("list_id");
        }
        if(!is_numeric($change) ||
            ($change != 0 &&
             $change != 1)){
            return ErrorCode::errorParam("change");
        }

        $key = CompanySetting::where("company_id", $companyId)
            ->whereNotNull("mc_key")
            ->select("mc_key")->first();
        if (empty($key) || empty($key->mc_key)) {
            return ErrorCode::errorOutGroupNotSetApiKey();
        }
        $mailChimp = new MailChimp($key->mc_key);
        if($change == 1){
            $customer->mc_count++;
            $result = $mailChimp->post("lists/{$listId}/members",["email_address"=>$email,"status"=>"subscribed"]);
        }else{
            $customer->mc_count--;
            $result = $mailChimp->delete("lists/{$listId}/members/".md5($email));
        }
        if(!empty($result) && !empty($result['type'])){
            return ErrorCode::errorOuterGroupSetting();
        }
        Customer::where("id",$customer->id)->update(["mc_count"=>$customer->mc_count]);
        return ErrorCode::success("success");
    }


    public function addMailChimpList(Request $request)
    {

    }
}