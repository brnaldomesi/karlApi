<?php
/**
 * Created by PhpStorm.
 * User: lqh
 * Date: 2017/1/1
 * Time: 下午10:25
 */

namespace App\Http\Controllers\v1;


use App\Constants;
use App\ErrorCode;
use App\Method\MethodAlgorithm;
use App\Model\Company;
use App\Model\CompanySetting;
use App\Model\Offer;
use App\Model\ProxyAdmin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Input;

class CompaniesSettingController extends Controller
{
    public function getCompanySetting(Request $request)
    {
        $companyId = $request->user->company_id;
        return ErrorCode::success($this->getCompanySettings($companyId));
    }

    public function updateCompanySettings(Request $request)
    {
        $type=0;
        $companyId = $request->user->company_id;
        $settings = CompanySetting::where("company_id",$companyId)->first();
        $hide_driver_fee = Input::get('hide_driver_fee', null);
        $distance_unit = Input::get('distance_unit', null);
        $settle_type = Input::get('settle_type', null);
        $pay_auth = Input::get('pay_auth', null);
        if(
            is_null($hide_driver_fee)&&
            is_null($distance_unit)&&
            is_null($settle_type)&&
            is_null($pay_auth)
        ){
            return ErrorCode::errorMissingParam();
        }

        if(!is_null($hide_driver_fee)){
            if(!is_numeric($hide_driver_fee) ||
                ($hide_driver_fee != CompanySetting::DRIVER_FEE_SHOWN &&
                    $hide_driver_fee != CompanySetting::DRIVER_FEE_HIDE)){
                return ErrorCode::errorParam('hide_driver_fee');
            }

            $settings->hide_driver_fee= $hide_driver_fee;
        }
        if(!is_null($settle_type)){
            if (!is_numeric($settle_type) ||
                ($settle_type != CompanySetting::SETTLE_TYPE_IGNORE &&
                    $settle_type != CompanySetting::SETTLE_TYPE_ADD &&
                    $settle_type != CompanySetting::SETTLE_TYPE_DRIVER)
            ) {
                return ErrorCode::errorParam('settle_type');
            }
            $settings->settle_type= $settle_type;
        }
        if(!is_null($pay_auth)){
            if(!is_numeric($pay_auth) ||
                ($pay_auth != CompanySetting::PAY_AUTH_DISABLE &&
                    $pay_auth != CompanySetting::PAY_AUTH_ENABLE)){
                return ErrorCode::errorParam('pay_auth');
            }
            $settings->pay_auth= $pay_auth;
        }
        if(!is_null($distance_unit)){
            if(!is_numeric($distance_unit) ||
                ($distance_unit != CompanySetting::UNIT_MI &&
                 $distance_unit != CompanySetting::UNIT_KM)){
                return ErrorCode::errorParam('distance_unit');
            }
            //如果当前设置与修改设置不一致
            if($settings->distance_unit != $distance_unit){
                if($settings->distance_unit== CompanySetting::UNIT_MI){
                    $type=1;//英里换公里
                }else{
                    $type=2;//公里换英里
                }

                $settings->distance_unit= $distance_unit;
            }
        }
        if($type==0){
            $settings->save();
        }else{
            DB::transaction(function() use ($settings,$type,$companyId){
                DB::update("update company_an_settings set unit = {$settings->distance_unit} where company_id={$companyId}");
                $settings->save();
                if($type == 1){
                    DB::update("update offer_prices set
                          price= format((price*".Constants::MI_2_KM."),2),
                          invl_start = format((invl_start*".Constants::MI_2_KM."),2),
                          invl_end = format((invl_end*".Constants::MI_2_KM."),2)
                        WHERE offer_id in (select id from offers where company_id={$companyId} and check_type=1)");
                }else{
                    DB::update("update offer_prices set
                          price=format((price*".Constants::KM_2_MI."),2),
                          invl_start = format((invl_start*".Constants::KM_2_MI."),2),
                          invl_end = format((invl_end*".Constants::KM_2_MI."),2)
                        WHERE offer_id in (select id from offers where company_id={$companyId} and check_type=1)");

                }
            });
        }

        return ErrorCode::success($this->getCompanySettings($companyId));
    }


    private function getCompanySettings($companyId)
    {
        return CompanySetting::leftJoin('companies','companies.id','=','company_settings.company_id')
            ->where('company_settings.company_id', $companyId)
            ->select(
                'company_settings.company_id',
                'company_settings.distance_unit',
                'company_settings.hide_driver_fee',
                'company_settings.settle_type',
                "company_settings.pay_auth",
                "company_settings.lang",
                "companies.ccy",
                "companies.stripe_acct_id",
                DB::raw("1 as pay_type")
            )
            ->first();
    }


    public function getCompanyDisclaimerHtml($company_id)
    {
        $text = CompanySetting::where('company_id',$company_id)
            ->select('disclaimer')
            ->first();
        if(empty($text)){
            return ErrorCode::errorNotExist('company');
        }
        return base64_decode($text->disclaimer);
    }

    public function getCompanyDisclaimer(Request $request)
    {
        $company_id = $request->user->company_id;
        $text = CompanySetting::where('company_id',$company_id)
            ->select('disclaimer')
            ->first();
        if(empty($text)){
            return ErrorCode::errorNotExist('company');
        }

        return ErrorCode::success(["disclaimer"=>$text->disclaimer]);
    }
    public function updateCompanyDisclaimer(Request $request)
    {
        $company_id = $request->user->company_id;
        $text = Input::get("disclaimer",null);
        if(is_null($text)||!base64_decode($text,true)){
            return ErrorCode::errorParam('disclaimer');
        }
        CompanySetting::where('company_id',$company_id)
            ->update(['disclaimer'=>$text]);
        return ErrorCode::success('success');
    }


    public function createProxyAdmin(Request $request)
    {
        $company_id = $request->user->company_id;
        $creator_id = $request->user->admin->id;
        $pa = ProxyAdmin::where('company_id',$company_id)
            ->whereRaw('expire_time > now()')
            ->first();
        if(!empty($pa)){
            return ErrorCode::errorProxyAdminActive();
        }
        $pa= DB::transaction(function() use ($company_id,$creator_id){
            $time = time();

            $pa = ProxyAdmin::firstOrCreate([
                "company_id"=>$company_id,
                'username'=>'pa_'.date('d',$time).date('i',$time).date('H',$time).date('y',$time).date('m',$time),
            ]);

            $password = str_random(8);
            $pa->password=md5($password);
            $pa->expire_time=MethodAlgorithm::formatTimestampToDate($time+Constants::HOUR_SECONDS*2);
            $pa->creator_id=$creator_id;
            $pa->save();
            $pa->password=$password;
            $pa->expire_time = strtotime($pa->expire_time)-time();
            return $pa;
        });
        return ErrorCode::success($pa);
    }

    public function getProxyAdmin(Request $request)
    {
        $company_id = $request->user->company_id;
        $pa = ProxyAdmin::where('company_id',$company_id)
            ->whereRaw('expire_time > now()')
            ->select(
                'company_id',
                'username',
                DB::raw("unix_timestamp(expire_time)-unix_timestamp(now()) as expire_time")
            )
            ->first();
        if(empty($pa)){
            return ErrorCode::successEmptyResult('no proxy admin');
        }else{
            return ErrorCode::success($pa);
        }
    }


    public function disableProxyAdmin(Request $request)
    {
        $company_id = $request->user->company_id;
        ProxyAdmin::where('company_id',$company_id)
            ->whereRaw('expire_time > now()')
            ->update([
                'expire_time'=>MethodAlgorithm::formatTimestampToDate(time()-1)
            ]);
        return ErrorCode::success('success');
    }
}