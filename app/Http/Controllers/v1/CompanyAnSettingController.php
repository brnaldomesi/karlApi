<?php

namespace App\Http\Controllers\v1;

use App\Constants;
use App\Method\MethodAlgorithm;
use App\Method\UrlSpell;
use App\Model\Bill;
use App\Model\Car;
use App\ErrorCode;
use App\Model\CarModel;
use App\Model\Company;
use App\Model\CompanyAnSetting;
use App\Model\LnAskRecord;
use App\Model\LnProvideRecord;
use \Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Input;

class CompanyAnSettingController extends Controller
{
    public function changeCompanyLnSetting(Request $request, $enable)
    {
        $company_id = $request->user->company_id;
        $setting = CompanyAnSetting::where('company_id', $company_id)->first();
        if (empty($setting)) {
            return ErrorCode::errorNotExist("company");
        }
        if($setting->locked==CompanyAnSetting::AN_LOCKED){
            return ErrorCode::errorCompanyAnSettingIsLocked();
        }
        
        if ($enable != CompanyAnSetting::LN_DISABLE && $enable != CompanyAnSetting::LN_ENABLE) {
            return ErrorCode::errorParam('enable');
        }
        $setting->ln = $enable;
        $setting->save();
        return ErrorCode::success($this->getComAnSetting($setting));
    }

    public function changeCompanyGnSetting(Request $request, $enable)
    {
        $company_id = $request->user->company_id;
        $setting = CompanyAnSetting::where('company_id', $company_id)->first();
        if (empty($setting)) {
            return ErrorCode::errorNotExist("company");
        }

        if($setting->locked==CompanyAnSetting::AN_LOCKED){
            return ErrorCode::errorCompanyAnSettingIsLocked();
        }
        if ($enable != CompanyAnSetting::GN_DISABLE && $enable != CompanyAnSetting::GN_ENABLE) {
            return ErrorCode::errorParam('enable');
        }
        $setting->gn = $enable;
        $setting->save();
        return ErrorCode::success($this->getComAnSetting($setting));
    }

    public function changeCompanyCombineSetting(Request $request, $enable)
    {
        $company_id = $request->user->company_id;
        $setting = CompanyAnSetting::where('company_id', $company_id)->first();
        if (empty($setting)) {
            return ErrorCode::errorNotExist("company");
        }

        if($setting->locked==CompanyAnSetting::AN_LOCKED){
            return ErrorCode::errorCompanyAnSettingIsLocked();
        }
        if ($enable != CompanyAnSetting::COMBINE_DISABLE && $enable != CompanyAnSetting::COMBINE_ENABLE) {
            return ErrorCode::errorParam('enable');
        }
        $setting->combine = $enable;
        $setting->save();
        return ErrorCode::success($this->getComAnSetting($setting));
    }
    public function changeCompanyRadiusSetting(Request $request)
    {
        $company_id = $request->user->company_id;
        $radius = Input::get("radius",null);
        if(is_null($radius) || !is_numeric($radius) || $radius<1 || $radius>50){
            return ErrorCode::errorParam('radius');
        }
        $setting = DB::transaction(function() use ($radius,$company_id){
            $setting = CompanyAnSetting::where('company_id', $company_id)->first();
            $setting->radius = $radius;
            $setting->save();
            DB::delete("delete from c2c_match where from_com_id={$company_id} or to_com_id={$company_id}");
            $company= Company::leftjoin("company_settings","company_settings.company_id","=","companies.id")
                ->where("companies.id",$company_id)
                ->select(
                    "companies.id",
                    "companies.lat",
                    "companies.lng",
                    DB::raw("if(company_settings.distance_unit =".Constants::UNIT_MI.",{$radius},".round($radius*Constants::KM_2_MI,2).") as radius")
                )
                ->first();
            DB::insert(
                "insert into c2c_match(from_com_id, to_com_id)
                    SELECT {$company->id},companies.id from company_an_settings 
                    left join companies on companies.id=company_an_settings.company_id 
                    where company_an_settings.company_id != {$company->id} and
                    {$company->radius}  > (".Constants::MI_EARTH_R . " * acos(cos(radians({$company->lat})) * cos(radians(companies.lat)) * cos(radians
                               (companies.lng) - radians({$company->lng})) + sin(radians({$company->lat})) * sin(radians(companies.lat))))
                               "
            );
            DB::insert(
                "insert into c2c_match (from_com_id, to_com_id) 
                    SELECT companies.id,{$company->id} from company_an_settings left join companies on companies.id=company_an_settings.company_id where company_an_settings.company_id != {$company->id} and
                    ((company_an_settings.unit = " . Constants::UNIT_MI . " and (company_an_settings.radius) > (" . Constants::MI_EARTH_R . " * acos(cos(radians({$company->lat})) * cos(radians(companies.lat)) * cos(radians
                               (companies.lng) - radians({$company->lng})) + sin(radians({$company->lat})) * sin(radians(companies.lat)))))
                               or (company_an_settings.unit = " . Constants::UNIT_KM . " AND (company_an_settings.radius*" . Constants::KM_2_MI . " ) > (" . Constants::MI_EARTH_R . " * acos(cos(radians({$company->lat})) * cos(radians(companies.lat)) * cos(radians
                               (companies.lng) - radians({$company->lng})) + sin(radians({$company->lat})) * sin(radians(companies.lat))))))
                               "
            );
            return $setting;
        });

        return ErrorCode::success($this->getComAnSetting($setting));
    }

    /**
     * 1.先判断model是否已经添加,
     * 2.再判断ln是否开启
     * 3.在判断model是否存在
     * @param Request $request
     * @param $model_id
     * @return string
     */

    public function addLnCarModelForAsk(Request $request, $model_id)
    {
        $modelIds = explode(",", MethodAlgorithm::decodeString($model_id));
        $company_id = $request->user->company_id;
        $setting = CompanyAnSetting::where("company_id", $company_id)->first();
        if (empty($setting)) {
            return ErrorCode::errorNotExist("company");
        }
        if($setting->locked==CompanyAnSetting::AN_LOCKED){
            return ErrorCode::errorCompanyAnSettingIsLocked();
        }
        if ($setting->ln == CompanyAnSetting::LN_DISABLE) {
            return ErrorCode::errorCompanyAnSettingLnError();
        }
        $carModelCount = CarModel::whereIn('id', $modelIds)->count();
        if ($carModelCount < count($modelIds)) {
            return ErrorCode::errorNotExist('some car models');
        }
        DB::transaction(function() use ($company_id,$modelIds){
            LnAskRecord::where('company_id', $company_id)
                ->update(["needed"=>LnAskRecord::NEEDED_NO]);
            LnAskRecord::where('company_id', $company_id)
                ->where("secret",LnAskRecord::SECRET_NO)
                ->where("needed",LnAskRecord::NEEDED_NO)
                ->delete();
            foreach ($modelIds as $modelId) {
                $record = LnAskRecord::firstOrNew([
                    "company_id" => $company_id,
                    "car_model_id" => $modelId
                ]);
                $record->needed = LnAskRecord::NEEDED;
                $record->save();
            }
        });
        return ErrorCode::success($this->getComAnSetting($setting));
    }

    public function addLnCarForProvider(Request $request, $car_id)
    {
        $carIds = explode(",", MethodAlgorithm::decodeString($car_id));
        $company_id = $request->user->company_id;
        $setting = CompanyAnSetting::where("company_id", $company_id)->first();
        if (empty($setting)) {
            return ErrorCode::errorNotExist("company");
        }
        if($setting->locked==CompanyAnSetting::AN_LOCKED){
            return ErrorCode::errorCompanyAnSettingIsLocked();
        }
        if ($setting->ln == CompanyAnSetting::LN_DISABLE) {
            return ErrorCode::errorCompanyAnSettingLnError();
        }
        $carCount = Car::where('cars.company_id', $company_id)
            ->whereIn("cars.id", $carIds)
            ->count();
        if ($carCount < count($carIds)) {
            return ErrorCode::errorNotExist("some cars");
        }
        DB::transaction(function() use ($company_id,$carIds){
            foreach ($carIds as $carId) {
                $record = LnProvideRecord::firstOrNew([
                    "company_id" => $company_id,
                    "car_id" => $carId
                ]);
                $record->provide = LnProvideRecord::PROVIDED;
                $record->secret = LnProvideRecord::SECRET_ASK;
                $record->save();
            }
        });

        return ErrorCode::success($this->getComAnSetting($setting));
    }

    public function removeLnCarModelForAsk(Request $request, $model_id)
    {
        $modelIds = explode(",", MethodAlgorithm::decodeString($model_id));
        $company_id = $request->user->company_id;
        $setting = CompanyAnSetting::where("company_id", $company_id)->first();
        if (empty($setting)) {
            return ErrorCode::errorNotExist("company");
        }
        if($setting->locked==CompanyAnSetting::AN_LOCKED){
            return ErrorCode::errorCompanyAnSettingIsLocked();
        }
        if ($setting->ln == CompanyAnSetting::LN_DISABLE) {
            return ErrorCode::errorCompanyAnSettingLnError();
        }

        DB::transaction(function() use ($company_id,$modelIds){
            LnAskRecord::where("company_id", $company_id)
                ->whereIn("car_model_id", $modelIds)
                ->update(["needed"=>LnAskRecord::NEEDED_NO]);
            LnAskRecord::where("company_id", $company_id)
                ->where("needed", LnAskRecord::NEEDED_NO)
                ->where("secret", LnAskRecord::SECRET_NO)
                ->delete();
        });
        return ErrorCode::success($this->getComAnSetting($setting));
    }

    public function removeLnCarForProvider(Request $request, $car_id)
    {
        $carIds = explode(",", MethodAlgorithm::decodeString($car_id));
        $company_id = $request->user->company_id;
        $setting = CompanyAnSetting::where("company_id", $company_id)->first();
        if (empty($setting)) {
            return ErrorCode::errorNotExist("company");
        }
        if($setting->locked==CompanyAnSetting::AN_LOCKED){
            return ErrorCode::errorCompanyAnSettingIsLocked();
        }
        if ($setting->ln == CompanyAnSetting::LN_DISABLE) {
            return ErrorCode::errorCompanyAnSettingLnError();
        }
        DB::transaction(function() use ($company_id,$carIds){
            LnProvideRecord::where("company_id", $company_id)
                ->whereIn("car_id", $carIds)
                ->update(["provide"=>LnProvideRecord::PROVIDE_NO]);
            LnProvideRecord::where("company_id", $company_id)
                ->where("secret", LnProvideRecord::SECRET_NO)
                ->where("provide",LnProvideRecord::PROVIDE_NO)
            ->delete();
        });
        return ErrorCode::success($this->getComAnSetting($setting));
    }

    private function getLnCarForProvide($company_id)
    {
        $categories = Car::leftjoin("car_models", "car_models.id", "=", "cars.car_model_id")
            ->leftjoin("car_categories", "car_categories.id", "=", "car_models.car_category_id")
            ->where("cars.company_id", $company_id)
            ->select(
                "car_categories.name as category_name",
                "car_categories.id as category_id"
            )
            ->groupBy("car_categories.id")
            ->orderBy("car_categories.id", "asc")
            ->get();
        foreach ($categories as $category) {
            $cars = Car::leftjoin("car_models", "car_models.id", "=", "cars.car_model_id")
                ->leftjoin("car_brands", "car_models.car_brand_id", "=", "car_brands.id")
                ->leftjoin("car_categories", "car_categories.id", "=", "car_models.car_category_id")
                ->leftjoin("ln_provide_records", "ln_provide_records.car_id", "=", "cars.id")
                ->where("cars.company_id", $company_id)
                ->where("car_categories.id", $category->category_id)
                ->select(
                    DB::raw("if(ln_provide_records.provide is null , 0 , ln_provide_records.provide) as selected"),
                    UrlSpell::getUrlSpell()->getCarsImgInDB($company_id, "123"),
                    "cars.bags_max",
                    "cars.seats_max",
                    "cars.year",
                    "cars.color",
                    "cars.car_model_id",
                    "cars.license_plate",
                    "cars.id as car_id",
                    "car_categories.name as category_name",
                    "car_brands.name as brand_name",
                    "car_models.name as model_name"
                )
                ->get();
            $category->cars = $cars;
        }


        return ($categories);
    }

    private function getLnCarModelForAsk($company_id)
    {
        $categories = Car::rightJoin("ln_provide_records", 'cars.id', '=', 'ln_provide_records.car_id')
            ->rightjoin("car_models", "car_models.id", "=", "cars.car_model_id")
            ->leftjoin("car_categories", "car_categories.id", "=", "car_models.car_category_id")
            ->where("cars.company_id","!=",$company_id)
            ->select(
                "car_categories.id as category_id",
                "car_categories.name as category_name"
            )
            ->groupBy("car_categories.id")
            ->get();
        foreach ($categories as $category) {
            $carModel = Car::rightJoin("ln_provide_records", 'cars.id', '=', 'ln_provide_records.car_id')
                ->leftjoin("car_models", "car_models.id", "=", "cars.car_model_id")
                ->leftjoin("car_categories", "car_categories.id", "=", "car_models.car_category_id")
                ->leftjoin("car_brands", "car_brands.id", "=", "car_models.car_brand_id")
                ->leftjoin(DB::raw("(select * from ln_ask_records WHERE company_id={$company_id} and needed=1) as ln_ask_records"), "ln_ask_records.car_model_id", "=", "car_models.id")
                ->whereRaw("cars.company_id in (select to_com_id from c2c_match where from_com_id={$company_id})")
                ->where("ln_provide_records.provide",LnProvideRecord::PROVIDED)
                ->select(
                    "car_models.name as model_name",
                    "car_models.seats_max",
                    "car_models.bags_max",
                    "car_brands.name as brand_name",
                    "car_models.id as car_model_id",
                    DB::raw("if(ln_ask_records.needed is null , 0,ln_ask_records.needed) as selected"),
                    DB::raw(UrlSpell::getUrlSpell()->getCarModelImageBD() . " as img")
                )
                ->where("car_models.car_category_id", $category->category_id)
                ->groupBy("car_models.id")
                ->get();
            $category->car_models = $carModel;
        }
        return ($categories);
    }

    public function getCompanyAnSetting(Request $request)
    {
        $company_id = $request->user->company_id;
        $setting = CompanyAnSetting::where("company_id", $company_id)->first();
        if (empty($setting)) {
            return ErrorCode::errorNotExist("setting");
        }

        return ErrorCode::success($this->getComAnSetting($setting));
    }

    private function getComAnSetting($setting)
    {
        DB::transaction(function() use ($setting){
            if($setting->locked == CompanyAnSetting::AN_UNLOCKED){
                $setting->provide = $this->getLnCarForProvide($setting->company_id);
                $setting->ask = $this->getLnCarModelForAsk($setting->company_id);
                $setting->booking_count = 10;
            }else{
                $setting->provide=[];
                $setting->ask=[];
                $setting->booking_count = Bill::leftjoin('bookings','bills.booking_id','=','bookings.id')
                ->whereRaw('bookings.company_id=bookings.exe_com_id')
                ->where('bookings.exe_com_id',$setting->company_id)
                ->count();

            }
            unset($setting->id);
        });
        return $setting;
    }


    public function setCompanyAnLocked($company_id)
    {
        $locked = Input::get('locked',null);
        if(is_null($locked)||
            !is_numeric($locked) ||
            ($locked != CompanyAnSetting::AN_LOCKED &&
             $locked != CompanyAnSetting::AN_UNLOCKED)){
            return ErrorCode::errorParam('locked');
        }
        CompanyAnSetting::where('company_id',$company_id)->update(["locked"=>$locked]);
        return ErrorCode::success('success');
    }
}