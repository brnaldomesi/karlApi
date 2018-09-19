<?php

namespace App\Http\Controllers\v1;


use App\ErrorCode;
use App\Method\MethodAlgorithm;
use App\Model\Company;
use App\Model\CompanyAnnex;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Input;

class AnnexController extends Controller
{
    public function getCompaniesApp($companyId, $plateForm)
    {
        $app = CompanyAnnex::where("company_id",$companyId)
            ->select(
                DB::raw("case when '{$plateForm}'='".CompanyAnnex::IOS_PLATFORM."' then
                ios_app
                when '{$plateForm}'='".CompanyAnnex::ANDROID_PLATFORM."' then
                android_app
                ELSE NULL END AS url"))->first();
        return redirect()->to($app->url);
    }


    public function companySetAppUrl($companyId,$platform)
    {
        $url = Input::get("url",null);
        if(empty($url) || !MethodAlgorithm::urlMatchRegex($url)){
            return ErrorCode::errorParam("url");
        }
        $platform = strtolower($platform);
        if (
            $platform != CompanyAnnex::IOS_PLATFORM &&
            $platform != CompanyAnnex::ANDROID_PLATFORM
        ){
            return ErrorCode::errorParam("platform");
        }

        return DB::transaction(function() use ($companyId,$url,$platform){
            $count = Company::where("id",$companyId)->count();
            if($count==0){
                return ErrorCode::errorNotExist("company");
            }
            CompanyAnnex::where("company_id",$companyId)->update([$platform."_app"=>$url]);
            return ErrorCode::success("success");
        });

    }

    public function checkCustomerAppVersion($company_id,$plate)
    {
        $version = Input::get("version",null);
        return CompanyAnnex::checkAppVersion($company_id,$version,$plate);
    }

    public function checkDriverAppVersion($plate)
    {
        $version = Input::get("version",null);
        return CompanyAnnex::checkAppVersion(0,$version,$plate);
    }

    public function updateCustomerAppVersion($company_id)
    {
        $version = Input::get('version',null);
        return CompanyAnnex::updateCompanyAppVersion($company_id,$version);
    }
    public function updateDriverAppVersion()
    {
        $version = Input::get('version',null);
        return CompanyAnnex::updateCompanyAppVersion(0,$version);
    }
}