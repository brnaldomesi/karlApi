<?php
/**
 * Created by PhpStorm.
 * User: lqh
 * Date: 2017/3/10
 * Time: 下午12:42
 */

namespace App\Method;


use Curl\Curl;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Lang;

class PushCenter
{
    private static $instance=null;

    /**
     * PushCenter constructor.
     */
    private function __construct(){}


    /**
     * @return PushCenter|null
     */
    public static function initInstance()
    {
         if(is_null(self::$instance)){
            self::$instance = new PushCenter();
         }
        return self::$instance;
    }

    public function sendAdminNotice($title, $msg, $companyId,$url=null)
    {
        $results = $this->getCompanyAdminsTokens($companyId);
        if(is_null($results) ){
            return;
        }
        if(is_null($url)){
            $url=$_SERVER["dashboard_url"];
        }
        foreach ($results as $result) {
            $this->sendPushCrewMessage($title,$msg,$result['token'],$url,$result['lang']);

        }
    }


    /**
     * @param $companyId
     * @return array|null
     */
    private function getCompanyAdminsTokens($companyId)
    {
        $results = \App\Model\Admin::leftjoin("users","admins.user_id","=","users.id")
            ->where("users.company_id",$companyId)
            ->whereNotNull("admins.web_push_token")
            ->where("admins.web_push_token","!=","")
            ->whereNotNull("admins.web_push_token")
            ->select(
                DB::raw("CONCAT('[',
         group_concat(
             CONCAT('{\"token\":\"',admins.web_push_token,'\"'),
             CONCAT(',\"lang\":\"',users.lang,'\"}')),
         ']') as results")
                )
            ->groupBy("users.company_id")
            ->first();
        if(empty($results)){
            return null;
        }else{
            return json_decode($results->results,true);
        }
    }



    /**
     * @param $title string
     * @param $message  string
     * @param $receiveIds array
     * @param $url string
     * @param $lang string
     */
    private function sendPushCrewMessage($title,$message,$receiveIds,$url,$lang)
    {
        app('translator')->setLocale($lang);
        $tle = Lang::get("admin_push.".$title);
        $msg = Lang::get("admin_push.".$message);

        $subscriberList = Array();
        $subscriberList[] = $receiveIds;

        $subscriberListArray = Array();
        $subscriberListArray['subscriber_list'] = $subscriberList;

        $date = [
            "title"=>$tle,
            "message"=>$msg,
            "url"=>$url,
            "subscriber_list"=>json_encode($subscriberListArray)
        ];
        $curl = new Curl();
        $curl->setHeader("Authorization","key=".$_SERVER['PUSHCREW_TOKEN']);
        $res = $curl->post("https://pushcrew.com/api/v1/send/list",$date);
        \Log::info("send push ".json_encode($res));
    }
}