<?php

namespace App\Model;

use App\ErrorCode;
use Illuminate\Database\Eloquent\Model;

class CompanyAnnex extends Model
{

    public $timestamps = false;

    const IOS_PLATFORM = "ios";
    const ANDROID_PLATFORM = "android";

    const NEW_UPDATE=1;
    const NOT_UPDATE=0;
    const API_SUPPORT=1;
    const API_NOT_SUPPORT=0;
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
       "company_id",
        "ios_app",
        "android_app",
        "ios_id",
        "ios_bundle",
        "ios_version",
        "android_id",
        "pkg_name",
        "android_version"
    ];
    public static function appVersionMatch($version)
    {
        $regex = "/^[0-9]{1,2}.[0-9]{1,3}.[0-9]{1,3}$/";
        return preg_match($regex, $version);
    }

    public static function appVersionCompare($oldVersion, $newVersion)
    {
        $oldVerNums = explode(".", $oldVersion);
        $newVerNums = explode(".", $newVersion);
        $error = false;
        for ($i = 0; $i < 3; $i++) {
            if (intval($newVerNums[$i]) > intval($oldVerNums[$i])) {
                break;
            } else if (intval($newVerNums[$i]) == intval($oldVerNums[$i])) {
                continue;
            } else {
                $error = true;
                break;
            }
        }
        if ($error) {
            throw new \Exception("version error");
        }
        return $newVersion;
    }

    public static function checkAppVersion($company_id,$version,$plate)
    {
        if(
            strtolower($plate) != CompanyAnnex::IOS_PLATFORM &&
            strtolower($plate) != CompanyAnnex::ANDROID_PLATFORM
        ){
            return ErrorCode::errorParam('plate');
        }
        if(!CompanyAnnex::appVersionMatch($version)){
            return ErrorCode::errorParam('version');
        }

        $company = CompanyAnnex::where('company_id',$company_id)->first();
        if(empty($company)){
            return ErrorCode::errorParam('the version not exist');
        }

        try{
            if($plate == CompanyAnnex::IOS_PLATFORM){
                if($version != CompanyAnnex::appVersionCompare($version ,$company->ios_version)){
                    return ErrorCode::success(['update'=>CompanyAnnex::NEW_UPDATE,"support"=>CompanyAnnex::API_SUPPORT]);
                }else{
                    return ErrorCode::success(['update'=>CompanyAnnex::NOT_UPDATE,"support"=>CompanyAnnex::API_SUPPORT]);
                }
            }else{
                if($version != CompanyAnnex::appVersionCompare($version ,$company->android_version)){
                    return ErrorCode::success(['update'=>CompanyAnnex::NEW_UPDATE,"support"=>CompanyAnnex::API_SUPPORT]);
                }else{
                    return ErrorCode::success(['update'=>CompanyAnnex::NOT_UPDATE,"support"=>CompanyAnnex::API_SUPPORT]);
                }
            }
        }catch(\Exception $ex){
            return ErrorCode::errorParam('version');
        }
    }

    public static function updateCompanyAppVersion($company_id, $version)
    {
        $company = CompanyAnnex::where('company_id',$company_id)->first();
        if(empty($company)){
            return ErrorCode::errorParam('the version not exist');
        }
        if($version != CompanyAnnex::appVersionCompare($company->android_version,$version)){
            $company->android_version = $version;
            $company->save();
            return ErrorCode::success("success");
        }else{
            return ErrorCode::errorParam("version");
        }
    }
}
