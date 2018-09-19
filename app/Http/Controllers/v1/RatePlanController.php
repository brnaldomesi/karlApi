<?php

namespace App\Http\Controllers\v1;

use App\ErrorCode;
use App\Model\RateRule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Input;

class RatePlanController extends Controller
{
    public function getRateRules()
    {
        $plan = RateRule::getRateRules();
        return ErrorCode::success($plan);
    }

    public function updateRateRules()
    {
        $rules = Input::get("rules",null);
        $rules = json_decode($rules,true);
        if(empty($rules)){
            return ErrorCode::errorParam('rules');
        }
        try{
            DB::transaction(function() use ($rules){
                RateRule::where('type',RateRule::RULE_TYPE_RATES)->delete();
                $rules = RateRule::sortRateRules($rules);
                foreach ($rules as $rule) {
                    RateRule::create($rule);
                }
            });
        }catch(\Exception $ex){
            return $ex->getMessage();
        }

        $plan = RateRule::getRateRules();
        return ErrorCode::success($plan);
    }
}