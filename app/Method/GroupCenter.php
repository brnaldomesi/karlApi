<?php
/**
 * Created by PhpStorm.
 * User: lqh
 * Date: 2017/5/11
 * Time: 下午4:51
 */

namespace App\Method;


use App\ErrorCode;
use App\Model\CustomerGroupBinders;
use App\Model\CustomerGroupMembers;
use DrewM\MailChimp\MailChimp;

class GroupCenter
{
    public static function addCustomerToGroup($key, $type, $groupOutId, $groupId, $customerId, $email, $firstName, $lastName)
    {
        switch ($type) {
            case CustomerGroupBinders::TYPE_MAIL_CHIMP:
                try {
                    $mailChimp = new MailChimp($key);
                    $mailChimp->post("lists/{$groupOutId}/members",
                        ["email_address" => $email,
                            "merge_fields" => [
                                "FNAME" => $firstName,
                                "LNAME" => $lastName,
                            ],
                            "status" => "subscribed"]);
                    CustomerGroupMembers::firstOrNew([
                        "group_id" => $groupId,
                        "customer_id" => $customerId,
                        "customer_id_code"=>$mailChimp->subscriberHash($email)
                    ]);
                    return true;
                } catch (\Exception $ex) {
                    return false;
                }
            default :
                return false;
        }
    }

    public static function removeCustomerFromGroup($key, $type, $groupOutId, $groupId, $customerId, $outCustomerId)
    {
        switch ($type) {
            case CustomerGroupBinders::TYPE_MAIL_CHIMP:
                try {
                    echo "out customer id ".$groupOutId;
                    $mailChimp = new MailChimp($key);
                    $method = "lists/{$groupOutId}/members/{$outCustomerId}";
                    $mailChimp->delete($method);
                    CustomerGroupMembers::where("group_id", $groupId)->where("customer_id", $customerId)->delete();
                    return true;
                } catch (\Exception $ex) {
                    echo $ex;
                    return false;
                }
            default :
                return false;
        }
    }


    public static function getGroupList($key, $type)
    {
        switch ($type) {
            case CustomerGroupBinders::TYPE_MAIL_CHIMP:
                $MailChimp = new MailChimp($key);
                $lists = $MailChimp->get('lists');
                if (isset($lists['status']) && $lists['status'] == 401) {
                    return null;
                } else {
                    if (!$lists) {
                        return [];
                    } else {
                        return $lists['lists'];
                    }
                }
            default :
                return null;
        }
    }

    public static function getCustomerGroupInfo($key, $type, $email)
    {
        switch ($type) {
            case CustomerGroupBinders::TYPE_MAIL_CHIMP:
                $mailChimp = new MailChimp($key);
                $memberList = $mailChimp->get("lists");
                if(!isset($memberList['lists'])){
                    return [];
                }
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
                    return [];
                }
                $member = $mailChimp->get("search-members", ["query" => $email]);
                foreach ($member['exact_matches']['members'] as $list) {
                    if(!empty($mcList[$list['list_id']])){
                        $mcList[$list['list_id']]["checked"] = 1;
                    }
                }
                if (empty($mcList)) {
                    return [];
                }
                return array_values($mcList);
            default :
                return null;
        }
    }


    public static function changeGroupMemberList($key,$type,$email,$firstName,$lastName,$outGroupId,$change)
    {
        switch ($type){
            case CustomerGroupBinders::TYPE_MAIL_CHIMP:
                $mailChimp = new MailChimp($key);
                if($change == 1){
                    $result = $mailChimp->post("lists/{$outGroupId}/members",["email_address"=>$email,
                        "merge_fields" => [
                            "FNAME" => $firstName,
                            "LNAME" => $lastName,
                        ]
                        ,"status"=>"subscribed"]);
                }else{
                    $result = $mailChimp->delete("lists/{$outGroupId}/members/".md5($email));
                }
                if(!empty($result) && !empty($result['type'])){
                    return false;
                }
                return true;
            default:return false;
        }

    }

}