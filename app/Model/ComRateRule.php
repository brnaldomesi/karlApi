<?php
/**
 * Created by PhpStorm.
 * User: lqh
 * Date: 2017/1/1
 * Time: 下午10:28
 */

namespace App\Model;


use App\ErrorCode;
use Illuminate\Database\Eloquent\Model;
use App\Constants;

class ComRateRule extends Model
{

    const CREATOR_TYPE_SUPER_ADMIN=1;
    const CREATOR_TYPE_SALE=0;

    const PAGE = 1;
    const PER_PAGE = 10;

    protected $fillable = [
        'company_id',
        'rate_id',
        'rate',
        'start_time',
        'end_time',
        'creator_type',
        'creator_id',
        'take_effect'
    ];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = [
        "created_at","updated_at"
    ];

    public static function comRateRules($param,$creatorId,$creator_type){
        $rule = array();
        $start = array();
        $end = array();
        $rules = array();
        $companyId = array();
        $time = time();
        foreach ($param as $arr){


            if(!is_numeric($arr['company_id'])){
                throw new \Exception(ErrorCode::errorParam('company_id'));
            }
            if(count($arr['rule']) == 0){
                $company = $arr['company_id'];
                \DB::update("UPDATE com_rate_rules SET take_effect = 0 WHERE company_id = {$company}");
                continue;
            }
            $rules = $arr['rule'];
            foreach ($rules as $rule){


                if(!is_numeric($rule['rate'] )||($rule['rate']>=1)){
                    throw new \Exception(ErrorCode::errorParam('rate'));
                }

                if(!is_numeric($rule['start_time'])&& $rule['start_time']<$time){
                    throw new \Exception(ErrorCode::errorParam('start_time'));
                }
                if(!is_numeric($rule['end_time']) && $rule['start_time']>=$rule['end_time']){
                    throw new \Exception(ErrorCode::errorParam('end_time'));
                }


                $rule['company_id'] = $arr['company_id'];
                $rule['rate'] = $rule['rate'];
                $rule['start_time'] = $rule['start_time'];
                $rule['end_time'] = $rule['end_time'];
                $rule['creator_type'] = $creator_type;
                $rule['creator_id'] = $creatorId;
                $rule['take_effect'] = 1;
                $comRules[] = $rule;

                $company = $arr['company_id'];
                $companyId[] = $arr['company_id'];

                $start[] = $rule['start_time'];
                $end[] = $rule['end_time'];

                \DB::update("UPDATE com_rate_rules SET take_effect = 0 WHERE company_id = {$company}");
            }
        }
        array_multisort($companyId , SORT_ASC,$start, $comRules);
        sort($start);
        sort($end);
        for ($i = 0 ; $i<count($start)-1;$i++){
            if($comRules[$i]['company_id']!==$comRules[$i+1]['company_id']){
                continue;
            }

            if($comRules[$i]['end_time']>$comRules[$i+1]['start_time']){
                throw new \Exception(ErrorCode::errorParam('time'));
            }

        }

        return $comRules;

    }


//    public static function checkTime($company_ids){
//        $results = ComRateRule::where('take_effect',1)
//            ->wherein('company_id',$company_ids)
//            ->select('company_id','start_time','end_time')
//            ->get();
//        foreach ($results as $result){
//            $result = json_decode($result,true);
//            $arr[]=$result;
//            $company[]= $result['company_id'];
//            $start[] = $result['start_time'];
//            $end[] = $result['end_time'];
//
//        }
//        array_multisort($company, SORT_ASC,$start, $arr);
//        for ($i = 0 ; $i<count($start)-1;$i++){
//            if($arr[$i]['company_id']!==$arr[$i+1]['company_id']){
//                continue;
//            }
//
//            if($arr[$i]['end_time']>$arr[$i+1]['start_time']){
//                throw new \Exception(ErrorCode::errorParam('time'));
//            }
//        }
//        return $results;
//
//    }
}