<?php
/**
 * Created by PhpStorm.
 * User: hyt
 * Date: 2017/8/30
 * Time: 17:32
 */

namespace app\Http\Controllers\v1;


use App\Constants;
use App\ErrorCode;
use App\Model\ComRateRule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Input;
use Illuminate\Http\Request;

class ComRateRulesController
{
    public function getComRateRules(){

        $page = Input::get('page',Constants::PAGE_DEFAULT);
        $perPage = Input::get('per_page',Constants::PER_PAGE_DEFAULT);

        $search = Input::get('search',null);

        $search = ComRateRule::leftjoin('companies','com_rate_rules.company_id','=','companies.id')
            ->leftjoin('users','com_rate_rules.creator_id','=','users.id')
            -> Where("take_effect",1)
            ->where(function ($query)use ($search){
                if(!empty($search)){
                    $query -> Where("companies.name","like","%$search%")
                        ->orWhere("com_rate_rules.company_id", $search )
                        ->orWhere("com_rate_rules.rate",$search )
                        ->orWhere("start_time",strtotime($search))
                        ->orWhere("end_time",strtotime($search));
                }
            })
            ->select(
                'com_rate_rules.company_id',
                'companies.name',
                'com_rate_rules.start_time',
                'com_rate_rules.end_time',
                'com_rate_rules.rate',
                'com_rate_rules.creator_type',
                'com_rate_rules.creator_id',
                'users.username')
            ->orderby('com_rate_rules.company_id','ASC')
            ->orderby('com_rate_rules.start_time','ASC')
            ->skip (($page-1)*$perPage)
            ->take($perPage)
            ->get();
        return $search;
    }

    public function updateComRateRules(Request $request){
        $param = Input::get('param',null);
        $param = json_decode($param,true);
        if(is_null($param) || !is_array($param)){
            return ErrorCode::errorParam('param');
        }

        $user = $request->user;
        if(isset($user->superadmin)){
            $creatorId = $user->superadmin->id;
            $creatorType = ComRateRule::CREATOR_TYPE_SUPER_ADMIN;
        }else{
            $creatorId = $user->sale->id;
            $creatorType = ComRateRule::CREATOR_TYPE_SALE;
        }

        try{
            DB::transaction(function ()use ($param,$creatorId,$creatorType){
                /**
                 * 1.判断company_id及param参数
                 *    1.1 如果param 为空(null||'')抛出错误 ,
                 *    1.2 如果company_id 为空(null || '')抛出错误，返回
                 *    1.3 如果param 为空数组([])，将该公司规则改为不可用
                 *    1.4 如果param不为空数组，进行参数判断及排序。
                 *
                 *
                 */


                $rules = ComRateRule::comRateRules($param,$creatorId,$creatorType);
                foreach ($rules as $rule){
                        ComRateRule::create($rule);
                }
//

            });
        }catch (\Exception $ex) {
            return $ex->getMessage();
        }
        return $this->getComRateRules();
    }

//    public function updateComRateRules(Request $request){
//        $param = Input::get('param',null);
//        $param = json_decode($param,true);
//        if(is_null($param) || !is_array($param)){
//            return ErrorCode::errorParam('param');
//        }
//        $user = $request->user;
//        if(isset($user->superadmin)){
//            $creatorId = $user->superadmin->id;
//            $creatorType = ComRateRule::CREATOR_TYPE_SUPER_ADMIN;
//        }else{
//            $creatorId = $user->sale->id;
//            $creatorType = ComRateRule::CREATOR_TYPE_SALE;
//        }
//
//
//        try{
//            DB::transaction(function ()use ($param,$creatorId,$creatorType){
//                $i = true;
//                $rules = array();
//                $company_ids = array();
//                foreach ($param as $item) {
//                    $rule_id = isset($item['rule_id'])?$item['rule_id']:null;
//                    $rate_rule = ComRateRule::where('id',$rule_id)->first();
//
//                    if(!is_null($rate_rule)){
//                        //删除
//                        if(empty($item['rate']) && empty($item['start_time']) && empty($item['end_time'])){
//                            $rate_rule->take_effect = 0;
//                            $rate_rule->save();
//                            $i = false;
//                            continue;
//                        }
//                        //修改
//                        $company_id = $rate_rule->company_id;
//                        $rate = isset($item['rate'])?$item['rate']:$rate_rule->rate;
//                        $start_time =strtotime($item['start_time'])?strtotime($item['start_time']):$rate_rule->start_time;
//                        $end_time = strtotime($item['end_time'])?strtotime($item['end_time']):$rate_rule->end_time;
//                        $rate_rule->take_effect = 0;
//                        $rate_rule->save();
//                    }else{
//                        $company_id = $item['company_id'];
//                        $rate = $item['rate'];
//                        $start_time = strtotime($item['start_time']);
//                        $end_time = strtotime($item['end_time']);
//
//                        \DB::update("UPDATE com_rate_rules SET take_effect = 0 WHERE company_id = {$company_id}");
//                    }
//
//                    if(!is_numeric($company_id)){
//                        throw new \Exception(ErrorCode::errorParam('company_id'));
//                    }
//                    if(!is_numeric($rate)||($rate>=1)){
//                        throw new \Exception(ErrorCode::errorParam('rate'));
//                    }
//                    if(is_null($start_time)){
//                        throw new \Exception(ErrorCode::errorParam('start_time'));
//                    }
//
//                    if($start_time>=$end_time){
//                        throw new \Exception(ErrorCode::errorParam('end_time'));
//                    }
//
//
//                    $rule['company_id'] = $company_id;
//                    $rule['rate'] = $rate;
//                    $rule['start_time'] = $start_time;
//                    $rule['end_time'] = $end_time;
//                    $rule['creator_type'] = $creatorType;
//                    $rule['creator_id'] = $creatorId;
//                    $rule['take_effect'] = 1;
//
//                    $i = true;
//                    $rules[] = $rule;
//                    $company_ids[] = $company_id;
//                }
//                if($i){
//                    foreach ($rules as $rule){
//                        ComRateRule::create($rule);
//                    }
//                    ComRateRule::checkTime($company_ids);
//                }
//            });
//        }catch (\Exception $ex) {
//            return $ex->getMessage();
//        }
//        return $this->getComRateRules();
//
//
//    }
//


}