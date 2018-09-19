<?php

namespace App\Http\Controllers\v1;

use App\ErrorCode;
use App\Jobs\VehicleLAJob;
use App\Method\UrlSpell;
use App\Method\MethodAlgorithm;
use App\Model\Booking;
use App\Model\Calendar;
use App\Model\CalendarEvent;
use App\Model\CalendarRecurringEvent;
use App\Model\Car;
use App\Model\CarBrand;
use App\Model\CarCategory;
use App\Model\CarModel;
use App\Model\CarModelImg;
use App\Model\Company;
use App\Model\DriverCar;
use App\Model\LnProvideRecord;
use App\Model\OfferDriverCar;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Input;

class CarsController extends Controller
{

    public function carsCount()
    {
        $result = Car::all()->count();
        return ErrorCode::success($result);
    }

    public function companyCarsCount(Request $request)
    {
        $company_id = $request->user->company_id;
        $result = Car::where('company_id', $company_id)
            ->get()
            ->count();
        return ErrorCode::success($result);
    }

    public function brands()
    {
        $result = CarBrand::orderBy('sort', 'asc')->get();
        if (empty($result)) {
            return ErrorCode::successEmptyResult('No brand');
        }
        return ErrorCode::success($result);
    }

    public function categories()
    {
        $result = CarCategory::orderBy('priority', 'asc')->get();
        if (empty($result)) {
            return ErrorCode::successEmptyResult('No category');
        }
        return ErrorCode::success($result);
    }

    public function models($brand_id)
    {
        if (empty($brand_id)) {
            return ErrorCode::errorParam('Need brand id');
        }
        $car_models = CarModel::where('car_brand_id', $brand_id)
            ->get();
        if (empty($car_models)) {
            return ErrorCode::successEmptyResult('No models in this brand');
        }
        foreach ($car_models as $car_model) {
            $images = CarModelImg::where('car_model_id', $car_model->id)
                ->select('id as image_id', UrlSpell::getUrlSpell()->getCarModelImgInDB())
                ->get();
            $car_model->model_imgs = $images;
        }

        return ErrorCode::success($car_models);
    }

    public function deleteCarModel($car_model_id)
    {
        try {
            DB::transaction(function () use ($car_model_id) {
                CarModelImg::where('car_model_id', $car_model_id)->delete();
                CarModel::where('id', $car_model_id)->delete();
            });
            return ErrorCode::success('success');
        } catch (\Exception $ex) {
            return $ex->getMessage();
        }
    }

    public function companyCarCategories($company_id)
    {
        if (empty($company_id)) {
            return ErrorCode::errorParam('Need company id');
        }

        $carCategories = Car::leftjoin("car_models","car_models.id","=",'cars.car_model_id')
            ->leftjoin("car_categories","car_categories.id","=","car_models.car_category_id")
            ->where("cars.company_id",$company_id)
            ->select("car_categories.id","car_categories.name","car_categories.description","car_categories.priority")
            ->groupBy("car_categories.id")
            ->orderBy("car_categories.priority","ASC")
            ->get();
        if (empty($carCategories)) {
            return ErrorCode::successEmptyResult("No cars in company");
        }
        return ErrorCode::success($carCategories);
    }

    public function companyCars(Request $request)
    {
        $company_id = $request->user->company_id;
        $token = Input::get("token", null);
        $cars = Car::select('cars.id as id', 'car_brands.name as brand', 'car_models.name as model',
            UrlSpell::getUrlSpell()->getCarsImgInDB($company_id, $token),
            DB::raw(Car::RATING_DB),
            'car_categories.name as category',
            'car_categories.id as category_id',
            'cars.seats_max',
            'cars.bags_max',
            'cars.year as year',
            'cars.color as color',
            'cars.license_plate',
            'cars.description')
            ->where('cars.company_id', '=', $company_id)
            ->leftjoin('car_models', 'cars.car_model_id', '=', 'car_models.id')
            ->leftjoin('car_brands', 'car_models.car_brand_id', '=', 'car_brands.id')
            ->leftjoin('car_categories', 'car_models.car_category_id', '=', 'car_categories.id')
            ->orderBy('updated_at', 'desc')
            ->get();
        if (empty($cars)) {
            return ErrorCode::successEmptyResult('No cars');
        }
        $count = Car::where('company_id', $company_id)->count();
        $result = ['total' => $count, 'cars' => $cars];
        return ErrorCode::success($result);
    }

    public function companyCarDetail(Request $request, $car_id)
    {
        if (empty($car_id)) {
            return ErrorCode::errorParam('Need car id');
        }
        $company_id = $request->user->company_id;
        $token = Input::get("token", null);

        // 判断是否夏时令
        $dst = MethodAlgorithm::checkDstForCar($car_id);

        $result = Car::leftjoin('car_models', 'cars.car_model_id', '=', 'car_models.id')
            ->leftjoin('car_brands', 'car_models.car_brand_id', '=', 'car_brands.id')
            ->leftjoin('car_categories', 'car_models.car_category_id', '=', 'car_categories.id')
            ->leftjoin('companies', 'cars.company_id', '=', 'companies.id')
            ->leftjoin('calendars', 'calendars.owner_id', '=', 'cars.id')
            ->leftjoin('car_model_imgs', 'car_model_imgs.image_path', '=', 'cars.img')
            ->where('cars.company_id', $company_id)
            ->where('cars.id', $car_id)
            ->where('calendars.type', Calendar::CAR_TYPE)
            ->select('cars.id as id',
                'car_brands.name as brand',
                'car_models.name as model',
                'car_categories.name as category',
                'cars.seats_max',
                'cars.bags_max',
                UrlSpell::getUrlSpell()->getCarsImgInDB($company_id, $token),
                DB::raw(Car::RATING_DB),
                'cars.license_plate',
                'cars.year as year',
                'cars.color as color',
                'cars.description',
                'cars.pre_time',
                DB::raw("case when car_model_imgs.id is null then '' else car_model_imgs.id end as image_id", ''),
                DB::raw("case when car_model_imgs.id is null then 1 else 0 end as type", ''),
                DB::raw("case when " . $dst . " then calendars.dst_routine else calendars.routine end as routine")
//                'calendars.routine'
            )
            ->first();
        if (empty($result)) {
            return ErrorCode::errorNoObject('car');
        }
        return ErrorCode::success($result);
    }

    public function newCar(Request $request)
    {
        $company_id = Input::get('company_id', null);
        $param = Input::get('param', null);
        $token = Input::get('token', null);

        try {
            $result = $this->createNewCar($request, $company_id,
                $param, $token);
            $this->dispatch(new VehicleLAJob($company_id));
            return ErrorCode::success($result);
        } catch (\Exception $ex) {
            return $ex->getMessage();
        }
    }

    public function newCompanyCar(Request $request)
    {
        $company_id = $request->user->company_id;
        $param = Input::get('param', null);
        $token = Input::get('token', null);
//        try {
            $result = $this->createNewCar($request, $company_id,
                $param, $token);
        $this->dispatch(new VehicleLAJob($company_id));
        return ErrorCode::success($result);
//        } catch (\Exception $ex) {
//            return $ex->getMessage();
//        }
    }

    public function updateCar(Request $request, $car_id)
    {
        $car = Car::where('id', $car_id)->first();
        if (empty($car)) {
            return ErrorCode::errorNotExist();
        }

        $param = Input::get('param',null);
        $token = Input::get('token',null);


        try {
            $result = $this->updateCarInfo($request, $car, $car->company_id, $param,$token);
            $this->dispatch(new VehicleLAJob( $car->company_id));
            return ErrorCode::success($result);
        } catch (\Exception $ex) {
            return $ex->getMessage();
        }
    }

    public function updateCompanyCar(Request $request, $car_id)
    {
        $car = Car::where('id', $car_id)->first();
        if (empty($car)) {
            return ErrorCode::errorNotExist();
        }
        $company_id = $request->user->company_id;
        if ($company_id != $car->company_id) {
            return ErrorCode::errorAdminUnauthorizedOperation();
        }
        $param = Input::get('param',null);
        $token = Input::get('token',null);
        try {
            $result = $this->updateCarInfo($request, $car, $company_id,
                $param, $token);
            $this->dispatch(new VehicleLAJob($company_id));
            return ErrorCode::success($result);
        } catch (\Exception $ex) {
            return $ex->getMessage();
        }
    }

    public function removeCar($car_id)
    {
        $car = Car::where('id', $car_id)->first();
        if (empty($car)) {
            return ErrorCode::errorNotExist();
        }

        try {
            $this->removeCars($car_id);
            return ErrorCode::success('success');
        } catch (\Exception $ex) {
            return $ex->getMessage();
        }
    }

    public function removeCompanyCar(Request $request, $car_id)
    {
        $car = Car::where('id', $car_id)->first();
        if (empty($car)) {
            return ErrorCode::errorNotExist();
        }
        $company_id = $request->user->company_id;
        if ($company_id != $car->company_id) {
            return ErrorCode::errorAdminUnauthorizedOperation();
        }

        try {
            $this->removeCars($car_id);
            $this->dispatch(new VehicleLAJob($company_id));
            return ErrorCode::success('success');
        } catch (\Exception $ex) {
            return $ex->getMessage();
        }
    }

    private function removeCars($car_id)
    {
        DB::transaction(function () use ($car_id) {
            $bookings = Booking::where('car_id', $car_id)
                ->where('appointed_at', '>', DB::raw('now()'))
                ->get();
            if ($bookings->count() > 0) {
                throw new \Exception(ErrorCode::errorCarDelete($bookings));
            }
//            $event = CalendarEvent::where('re_owner_id',$car_id)
//                ->where('re_type',Calendar::CAR_TYPE)
//                ->where('start_time','>',DB::raw('now()'))
//                ->first();
//            if (!empty($booking) || !empty($event)) {
//                throw new \Exception(ErrorCode::errorCarDelete($booking));
//            }
            Car::where('id', $car_id)->delete();
            DriverCar::where('car_id', $car_id)->delete();
            OfferDriverCar::where('car_id', $car_id)->delete();
            LnProvideRecord::where('car_id',$car_id)->delete();
            Calendar::where([['calendars.owner_id', $car_id], ['calendars.type', Calendar::CAR_TYPE]])->delete();
            CalendarRecurringEvent::where([['calendar_recurring_events.owner_id', $car_id], ['calendar_recurring_events.owner_type', Calendar::CAR_TYPE]])->delete();
            CalendarEvent::where([['calendar_events.re_owner_id', $car_id], ['calendar_events.re_type', Calendar::CAR_TYPE]])->delete();
        });
    }

    private function createNewCar(Request $request, $company_id, $param, $token)
    {
        $param = json_decode($param, true);

        if(is_null($param) || !is_array($param)){
            throw new \Exception(ErrorCode::errorParam('param'));
        }
        return DB::transaction(function () use (
            $request, $company_id, $param, $token
        ) {
            $car_model_id = isset($param['car_model_id'])?$param['car_model_id']:null;
            $license_plate = isset($param['license_plate'])?$param['license_plate']:null;
            $description = isset($param['description'])?$param['description']:null;
            $calendar = isset($param['calendar'])?$param['calendar']:null;
            $pre_time = isset($param['pre_time'])?$param['pre_time']:30;
            $type = isset($param['type'])?$param['type']:null;
            $year = isset($param['year'])?$param['year']:null;
            $color = isset($param['color'])?$param['color']:null;
            $seats_max = isset($param['seats_max'])?$param['seats_max']:null;
            $bags_max = isset($param['bags_max'])?$param['bags_max']:null;

            if (is_null($car_model_id) ||
                is_null($company_id) ||
                is_null($license_plate) ||
                is_null($description) ||
                is_null($calendar) ||
                is_null($type) ||
                is_null($color) ||
                is_null($bags_max) ||
                is_null($seats_max) ||
                is_null($pre_time) ||
                is_null($year)
            ) {
                throw new \Exception(ErrorCode::errorMissingParam());
            }
            $carModel = CarModel::where('id', $car_model_id)->first();
            if (empty($carModel)) {
                throw new \Exception(ErrorCode::errorParam('car model id'));
            }
            if(!is_numeric($bags_max)||$bags_max <0){
                throw new \Exception(ErrorCode::errorParam('bags max'));
            }

            if(!is_numeric($seats_max)||$seats_max <0){
                throw new \Exception(ErrorCode::errorParam('seats max'));
            }
            if(!is_numeric($pre_time)||$pre_time <0){
                throw new \Exception(ErrorCode::errorParam('preparation time'));
            }

            $company = Company::where('id', $company_id)->first();
            if (empty($company)) {
                throw new \Exception(ErrorCode::errorParam('company id'));
            }
            $carModel = CarModel::where('id', $car_model_id)->first();
            if (empty($carModel)) {
                throw new \Exception(ErrorCode::errorParam('car model id'));
            }

            if (empty($year)) {
                throw new \Exception(ErrorCode::errorParam('year'));
            }
            if (empty($color)) {
                throw new \Exception(ErrorCode::errorParam('color'));
            }


            //验证calender
            if (empty($calendar) || !is_array($calendar)) {
                throw new \Exception(ErrorCode::errorParam('calender error'));
            } else {
                if (sizeof($calendar) != 7) {
                    throw new \Exception(ErrorCode::errorParam('calender error'));
                } else {
                    foreach ($calendar as $cal) {
                        if (!preg_match("/[0,1]{48}/", $cal)) {
                            throw new \Exception(ErrorCode::errorParam('calender error'));
                        }
                    }
                }
            }

            $create = ['car_model_id' => $car_model_id,
                'company_id' => $company_id,
                'license_plate' => $license_plate,
                'description' => $description,
                'pre_time' => $pre_time,
                'year' => $year,
                'color' => $color,
                'bags_max' => $bags_max,
                'seats_max' => $seats_max,
            ];
            $car = Car::create($create);
            if (empty($car)) {
                throw new \Exception(ErrorCode::errorDB());
            }

            switch ($type) {
                case 0:
                    $file_id = isset($param['car_img'])?$param['car_img']:null;
                    if (is_null($file_id)) {
                        throw new \Exception(ErrorCode::errorMissingParam('default image id'));
                    }
                    $filePath = CarModelImg::where([['id', $file_id], ['car_model_id', $car_model_id]])->first();
                    if (empty($filePath)) {
                        throw new \Exception(ErrorCode::errorNotExist('model image id'));
                    }
                    $car->img = $filePath->image_path;
                    break;
                case 1:
                    if (!$request->hasFile('car_img')) {
                        throw new \Exception(ErrorCode::errorMissingParam());
                    }
                    if (!$request->file('car_img')->isValid()) {
                        throw new \Exception(ErrorCode::errorFileUpload());
                    }
                    $carFile = $request->file('car_img');
                    $fileMode = $carFile->getMimeType();
                    if (strtolower($fileMode) != strtolower('image/png')
                        && strtolower($fileMode) != strtolower('image/jpg')
                        && strtolower($fileMode) != strtolower('image/jpeg')
                    ) {
                        throw new \Exception(ErrorCode::errorFileType());
                    }
                    $fileType = explode('/', $fileMode);
                    $carFileDirPath = str_replace('c_id', $company_id, $this->carImgFilePath);
                    $filePath = $carFile->move($carFileDirPath, $car->id . "." . $fileType[1]);
                    $car->img = $filePath;
                    break;
                default :
                    throw new \Exception(ErrorCode::errorParam('error type'));
            }
            $car->save();

            $company = Company::select('id','dst')->where('id', $company_id)->first();
            $calendar_dst = $company->dst==null ? 0 : $company->dst;

            // 判断是否夏时令
            $dst = MethodAlgorithm::checkDstForCar($car->id);

            if ($dst) {
                Calendar::create(['type' => Calendar::CAR_TYPE,
                    'owner_id' => $car->id,
                    'routine' => MethodAlgorithm::shiftString($calendar, true),
                    'dst_routine' => json_encode($calendar),
                    'dst' => $calendar_dst,
                    'company_id' => $company_id]);
            } else {
                Calendar::create(['type' => Calendar::CAR_TYPE,
                    'owner_id' => $car->id,
                    'routine' => json_encode($calendar),
                    'dst_routine' => MethodAlgorithm::shiftString($calendar),
                    'dst' => $calendar_dst,
                    'company_id' => $company_id]);
            }

            $this->spellCarImageUrl($car, $token);
            return $this->getCarDetail($car->id, $company_id, $token);
        });
    }

    private function updateCarInfo(Request $request, $car, $company_id,
                                   $param, $token)
    {
        $param = json_decode($param, true);

        if(is_null($param) || !is_array($param)){
            throw new \Exception(ErrorCode::errorParam('param'));
        }

        return DB::transaction(function () use (
            $request, $car, $company_id,
            $param, $token
        ) {

            $car_model_id = isset($param['car_model_id'])?$param['car_model_id']:null;
            $license_plate = isset($param['license_plate'])?$param['license_plate']:null;
            $description = isset($param['description'])?$param['description']:null;
            $calendar = isset($param['calendar'])?$param['calendar']:null;
            $pre_time = isset($param['pre_time'])?$param['pre_time']:null;
            $type = isset($param['type'])?$param['type']:null;
            $year = isset($param['year'])?$param['year']:null;
            $color = isset($param['color'])?$param['color']:null;
            $seats_max = isset($param['seats_max'])?$param['seats_max']:null;
            $bags_max = isset($param['bags_max'])?$param['bags_max']:null;

            if (is_null($license_plate) &&
                is_null($calendar) &&
                is_null($car_model_id) &&
                is_null($type) &&
                is_null($year) &&
                is_null($color) &&
                is_null($pre_time) &&
                is_null($description)
            ) {
                throw new \Exception(ErrorCode::errorMissingParam());
            }

            if (!is_null($car_model_id)) {
                if (empty($car_model_id) || !is_numeric($car_model_id)) {
                    throw new \Exception(ErrorCode::errorParam('car_model_id'));
                } else {
                    $carModel = CarModel::where('id', $car_model_id)->first();
                    if (empty($carModel)) {
                        throw new \Exception(ErrorCode::errorNotExist('car model'));
                    }
                    $car->car_model_id = $car_model_id;
                }
            }

            if (!is_null($type) && is_numeric($type)) {
                switch ($type) {
                    case 0:
                        $file_id = isset($param['car_img'])?$param['car_img']:null;
                        if (is_null($file_id)) {
                            throw new \Exception(ErrorCode::errorMissingParam('default image id'));
                        }
                        $filePath = CarModelImg::where([['id', $file_id], ['car_model_id', $car_model_id]])->first();
                        if (empty($filePath)) {
                            throw new \Exception(ErrorCode::errorNotExist('model image id'));
                        }
                        $car->img = $filePath->image_path;
                        break;
                    case 1:
                        if (!$request->hasFile('car_img')) {
                            throw new \Exception(ErrorCode::errorMissingParam());
                        }
                        if (!$request->file('car_img')->isValid()) {
                            throw new \Exception(ErrorCode::errorFileUpload());
                        }
                        $carFile = $request->file('car_img');
                        $fileMode = $carFile->getMimeType();
                        if (strtolower($fileMode) != strtolower('image/png')
                            && strtolower($fileMode) != strtolower('image/jpg')
                            && strtolower($fileMode) != strtolower('image/jpeg')
                        ) {
                            throw new \Exception(ErrorCode::errorFileType());
                        }
                        $fileType = explode('/', $fileMode);
                        $carFileDirPath = str_replace('c_id', $company_id, $this->carImgFilePath);
                        $filePath = $carFile->move($carFileDirPath, $car->id . "." . $fileType[1]);
                        $car->img = $filePath;
                        break;
                    default :
                        throw new \Exception(ErrorCode::errorParam('error type'));
                }
            }

            if (!is_null($year)) {
                $car->year = $year;
            }

            if (!is_null($color)) {
                $car->color = $color;
            }

            if (!empty($license_plate)) {
                $car->license_plate = $license_plate;
            }
            if (!empty($description)) {
                $car->description = $description;
            }
            if (!empty($calendar)) {
                if (!is_array($calendar)) {
                    throw new \Exception(ErrorCode::errorParam('calender error'));
                } else {
                    if (sizeof($calendar) != 7) {
                        throw new \Exception(ErrorCode::errorParam('calender error'));
                    } else {
                        foreach ($calendar as $cal) {
                            if (!preg_match("/[0,1]{48}/", $cal)) {
                                throw new \Exception(ErrorCode::errorParam('calender error'));
                            }
                        }
                    }
                }

                $carCalendar = Calendar::where("type", Calendar::CAR_TYPE)->where('owner_id', $car->id)->first();

                // 判断是否夏时令
                $dst = MethodAlgorithm::checkDstForCar($car->id);

                if (empty($carCalendar)) {

                    $company = Company::select('id','dst')->where('id', $company_id)->first();
                    $calendar_dst = $company->dst==null ? 0 : $company->dst;

                    if ($dst) {
                        Calendar::create(['type' => Calendar::CAR_TYPE,
                            'owner_id' => $car->id,
                            'routine' => MethodAlgorithm::shiftString($calendar, true),
                            'dst_routine' => json_encode($calendar),
                            'dst' => $calendar_dst,
                            'company_id' => $company_id]);
                    } else {
                        Calendar::create(['type' => Calendar::CAR_TYPE,
                            'owner_id' => $car->id,
                            'routine' => json_encode($calendar),
                            'dst_routine' => MethodAlgorithm::shiftString($calendar),
                            'dst' => $calendar_dst,
                            'company_id' => $company_id]);
                    }

                } else {

                    if ($dst) {
                        $carCalendar->dst_routine = json_encode($calendar);
                        $carCalendar->routine = MethodAlgorithm::shiftString($calendar, true);
                    } else {
                        $carCalendar->routine = json_encode($calendar);
                        $carCalendar->dst_routine = MethodAlgorithm::shiftString($calendar);
                    }

                    $carCalendar->save();
                }
            }
            if(!is_null($bags_max)){
                if(!is_numeric($bags_max)||$bags_max<0){
                    throw new \Exception(ErrorCode::errorParam('bags_max'));
                }else{
                    $car->bags_max = $bags_max;
                }
            }
            if(!is_null($seats_max)){
                if(!is_numeric($seats_max)||$seats_max<0){
                    throw new \Exception(ErrorCode::errorParam('seats_max'));
                }else{
                    $car->seats_max = $seats_max;
                }
            }
            if(!is_null($pre_time)){
                if(!is_numeric($pre_time)||$pre_time<0){
                    throw new \Exception(ErrorCode::errorParam('preparation_time'));
                }else{
                    $car->pre_time = $pre_time;
                }
            }


            if (!$car->save()) {
                throw new \Exception(ErrorCode::errorDB());
            }


            $this->spellCarImageUrl($car, $token);
            return $this->getCarDetail($car->id, $company_id, $token);
        });
    }

    private function getCarDetail($car_id, $company_id, $token)
    {
        // 判断是否夏时令
        $dst = MethodAlgorithm::checkDstForCompany($company_id);

        return Car::select('cars.id as id',
            'car_brands.name as brand',
            'car_models.name as model',
            'car_categories.name as category',
            'cars.seats_max',
            'cars.bags_max',
            UrlSpell::getUrlSpell()->getCarsImgInDB($company_id, $token),
            'cars.license_plate',
            'cars.year as year',
            'cars.color as color',
            'cars.description',
            DB::raw("case when " . $dst . " then calendars.dst_routine else calendars.routine end as routine")
        )
            ->where([['cars.id', $car_id], ['cars.company_id', $company_id]])
            ->where('calendars.type', Calendar::CAR_TYPE)
            ->leftjoin('car_models', 'cars.car_model_id', '=', 'car_models.id')
            ->leftjoin('car_brands', 'car_models.car_brand_id', '=', 'car_brands.id')
            ->leftjoin('car_categories', 'car_models.car_category_id', '=', 'car_categories.id')
            ->leftjoin('companies', 'cars.company_id', '=', 'companies.id')
            ->leftjoin('calendars', 'calendars.owner_id', '=', 'cars.id')
            ->first();
    }


    private function spellCarImageUrl($car, $token)
    {
        $middle = str_replace('c_id', $car->company_id, $this->carImage);
        $middle = str_replace('car_id', $car->id, $middle);
        $middle = $middle . '/' . md5($car->updated_at) . '?token=' . $token;
        $car->img = $this->serverHead . $middle;
    }


    public function getAllCarsAndDrivers(Request $request)
    {
        $token = $request->user->token;
        $company_id = $request->user->company_id;

        return ErrorCode::success($this->getDriverAndCarsInfo($company_id, $token));
    }

    private function getDriverAndCarsInfo($company_id, $token)
    {
        $carCategories = CarCategory::leftjoin('car_models', 'car_models.car_category_id', '=', 'car_categories.id')
            ->leftjoin('cars', 'cars.car_model_id', '=', 'car_models.id')
            ->select('car_categories.name as category',
                'car_categories.id as category_id')
            ->where('cars.company_id', $company_id)
            ->groupBy('category_id')
            ->get();
        foreach ($carCategories as $carCategory) {
            $cars = Car::leftjoin('driver_cars', 'cars.id', '=', 'driver_cars.car_id')
                ->leftjoin('car_models', 'cars.car_model_id', '=', 'car_models.id')
                ->leftjoin('car_brands', 'car_models.car_brand_id', '=', 'car_brands.id')
                ->leftjoin('car_categories', 'car_models.car_category_id', '=', 'car_categories.id')
                ->select(
                    'cars.id as car_id',
                    'cars.license_plate',
                    'cars.year as year',
                    'cars.color as color',
                    'cars.seats_max as seats_max',
                    'cars.bags_max as bags_max',
                    'car_models.name as model',
                    'car_brands.name as brand',
                    'car_categories.name as category',
                    'car_categories.id as category_id',
                    UrlSpell::getUrlSpell()->getCarsImgInDB($company_id, $token)
                )
                ->where('cars.company_id', $company_id)
                ->where('car_categories.id', $carCategory->category_id)
                ->groupBy('driver_cars.car_id')
                ->get();
            foreach ($cars as $car) {
                $drivers = DriverCar::leftJoin('drivers','drivers.id','=','driver_cars.driver_id')
                    ->leftJoin("users","users.id","=","drivers.user_id")
                    ->where("driver_cars.car_id",$car->car_id)
                    ->groupBy("driver_cars.driver_id")
                    ->select(
                        "driver_cars.car_id",
                        "driver_cars.driver_id",
                        'users.address','users.lat','users.lng',
                        "users.gender",
                        "users.mobile",
                        "users.first_name",
                        "users.last_name",
                        "users.mobile",
                        "users.email",
                        "drivers.delay_time"
                    )
                    ->get();
                $car->drivers = $drivers;
            }
            $carCategory->cars = $cars;
        }


        return $carCategories;
    }

    public function platformAllModels()
    {
        $brands = CarBrand::select(
            "id as car_brand_id",
            "name as car_brand_name",
            "sort as car_brand_sort"
        )->orderBy('sort', 'asc')->get();
        if (empty($brands)) {
            return ErrorCode::successEmptyResult('No brand');
        }
        foreach ($brands as $brand) {
            $car_models = CarModel::where('car_brand_id', $brand->car_brand_id)
                ->select(
                    "id as car_model_id",
                    "car_brand_id",
                    "car_category_id",
                    "name as car_model_name",
                    "seats_max",
                    "bags_max",
                    "sort as car_model_sort"
                )
                ->get();
            foreach ($car_models as $car_model) {
                $images = CarModelImg::where('car_model_id', $car_model->car_model_id)
                    ->select('id as image_id', UrlSpell::getUrlSpell()->getCarModelImgInDB())
                    ->get();
                $car_model->model_imgs = $images;
            }
            $brand->car_models = $car_models;
        }
        return ErrorCode::success($brands);
    }

}

