<?php
/**
 * Created by PhpStorm.
 * User: liqihai
 * Date: 16/2/29
 * Time: 17:09
 */

namespace App\Model;

use App\ErrorCode;
use Illuminate\Database\Eloquent\Model;

class RateRule extends Model{
    const RULE_TYPE_FREE=0;
    const RULE_TYPE_RATES=1;
    const RULE_TYPE_FEE=2;
    protected $fillable = [
        'type','invl_start',
        'invl_end','rate'
    ];

    protected $hidden = [
        'updated_at','created_at'
    ];


    public static function getRateRules()
    {
        return RateRule::where("type",RateRule::RULE_TYPE_RATES)
            ->get();
    }


    public static function sortRateRules($rules)
    {
        if (count($rules) == 1) {
            $rules[0]['type']=1;
            return $rules;
        }
        $start = [];
        $end = [];
        for ($i = 0; $i < count($rules); $i++) {
            $rule = $rules[$i];
            $rules[$i]['type'] = self::RULE_TYPE_RATES;
            if (!isset($rule['invl_start']) || !isset($rule['invl_end']) || !isset($rule['rate'])) {
                throw new \Exception(ErrorCode::errorParam('rates'));
            }
            if (!is_numeric($rule['invl_start']) || $rule['invl_start'] < 0) {
                throw new \Exception(ErrorCode::errorParam('rate interval start'));
            }
            if (!is_numeric($rule['invl_end']) || $rule['invl_end'] < $rule['invl_start']) {
                throw new \Exception(ErrorCode::errorParam('rate interval end'));
            }
            if (!is_numeric($rule['rate']) || $rule['rate'] < 0) {
                throw new \Exception(ErrorCode::errorParam('rate'));
            }
            if (isset($start[$rule['invl_start']])) {
                throw new \Exception(ErrorCode::errorParam('rate start repeat'));
            }
            if (isset($end[$rule['invl_end']])) {
                throw new \Exception(ErrorCode::errorParam('rate end repeat'));
            }

            $start[$rule['invl_start']] = $i;
            $end[$rule['invl_end']] = $i;
        }
        $startKey = array_keys($start);
        sort($startKey);
        $endKey = array_keys($end);
        sort($endKey);
        $tempRules = [];
        for ($i = 0; $i < count($endKey); $i++) {
            if ($i < count($endKey) - 1) {
                if ($startKey[$i + 1] != $endKey[$i]) {
                    throw new \Exception(ErrorCode::errorParam('rates sort'));
                }
            }
            array_push($tempRules, $rules[$end[$endKey[$i]]]);
        }
        return $tempRules;
    }
}