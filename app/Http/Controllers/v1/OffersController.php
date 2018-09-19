<?php

namespace App\Http\Controllers\v1;

use App\Method\MethodAlgorithm;
use App\Method\OfferMatchAlgorithm;
use App\Method\UrlSpell;
use App\Model\Booking;
use App\Model\Calendar;
use App\Constants;
use App\ErrorCode;
use App\Model\CompanySetting;
use App\Model\Driver;
use App\Model\Offer;
use App\Model\OfferDriverCar;
use App\Model\OfferOption;
use App\Model\Option;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Input;

class OffersController extends Controller
{

    public function hasOffers($company_id)
    {
        $type = Input::get('type', null);
        $d_lat = Input::get('d_lat', null);
        $d_lng = Input::get('d_lng', null);
        $a_lat = Input::get('a_lat', null);
        $a_lng = Input::get('a_lng', null);
        $unit = Input::get('unit', CompanySetting::UNIT_MI);
        $estimate_distance = Input::get('estimate_distance', null);
        $estimate_duration = Input::get('estimate_duration', null);
        $d_is_airport = Input::get('d_is_airport', 0);
        $a_is_airport = Input::get('a_is_airport', 0);
        return (new OfferMatchAlgorithm())->returnServiceCheck($type, $d_lng, $d_lat, $a_lng, $a_lat,
            $estimate_distance,$unit, $estimate_duration, $company_id,
            $d_is_airport, $a_is_airport);
    }

    public function getOffers()
    {
        $page = Input::get('page', Constants::PAGE_DEFAULT);
        $per_page = Input::get('per_page', Constants::PER_PAGE_DEFAULT);
        if ($per_page > 100 || $per_page < 1 || !is_numeric($per_page)) {
            $per_page = Constants::PER_PAGE_DEFAULT;
        }
        if ($page < 1 || !is_numeric($page)) {
            $page = Constants::PAGE_DEFAULT;
        }
        $skip = $per_page * ($page - 1);


        $result = Offer::leftjoin('companies', 'companies.id', '=', 'offers.company_id')
            ->select('offers.id as offer_id', 'offers.name as offer_name', 'companies.name as company_name', 'offers.id as offer_id',
                'offers.company_id', 'offers.type',
                'offers.description',
                'offers.d_radius', 'offers.a_radius',
                'offers.price', 'offers.calc_method')
            ->skip($skip)
            ->take($per_page)
            ->get();
        return ErrorCode::success($result);
    }

    public function companyGetOffers(Request $request)
    {

//        $page = Input::get('page', Constants::PAGE_DEFAULT);
//        $per_page = Input::get('per_page', Constants::PER_PAGE_DEFAULT);
//        if ($per_page > 100 || $per_page < 1 || !is_numeric($per_page)) {
//            $per_page = Constants::PER_PAGE_DEFAULT;
//        }
//        if ($page < 1 || !is_numeric($page)) {
//            $page = Constants::PAGE_DEFAULT;
//        }
//        $skip = $per_page * ($page - 1);
        $company_id = $request->user->company_id;

        // 判断是否夏时令
        $dst = MethodAlgorithm::checkDstForCompany($company_id);

        $offers = Offer::leftjoin('companies', 'companies.id', '=', 'offers.company_id')
            ->leftjoin('calendars', 'calendars.owner_id', '=', 'offers.id')
            ->leftjoin("offer_driver_cars", "offer_driver_cars.offer_id", "=", "offers.id")
            ->leftjoin('offer_options', 'offer_options.offer_id', '=', 'offers.id')
            ->leftjoin("cars", "cars.id", '=', 'offer_driver_cars.car_id')
            ->leftjoin(DB::raw("(SELECT 
  CONCAT('[',
         group_concat(
             CONCAT('{\"invl_start\":\"',ROUND(invl_start,2),'\"'),
             CONCAT(',\"invl_end\":\"',ROUND(invl_end,2),'\"'),
             CONCAT(',\"price\":\"',ROUND(price,2)),'\"}'),
         ']')AS prices,offer_id
FROM offer_prices 
GROUP BY offer_id) as op"), "offers.id", "=", "op.offer_id")
            ->leftjoin("car_models", "car_models.id", '=', 'cars.car_model_id')
            ->leftjoin("car_categories", "car_categories.id", "=", "car_models.car_category_id")
            ->where('offers.company_id', $company_id)
            ->where('calendars.type', Calendar::OFFER_TYPE)
            ->select('offers.id as offer_id',
                'offers.name as offer_name',
                'companies.name as company_name',
                'offers.id as offer_id',
                'offers.company_id',
                'offers.type',
                'offers.description',
                'offers.d_radius',
                'offers.d_is_port',
                'offers.d_port_price',
                'offers.a_radius',
                'offers.a_is_port',
                'offers.a_port_price',
                'op.prices',
                'offers.calc_method',
                DB::raw('ifnull(count(DISTINCT offer_options.option_id),0) as option_count'),
                DB::raw('ifnull(count(DISTINCT offer_driver_cars.car_id),0) as car_count'),
                DB::raw("ifnull(group_concat(DISTINCT car_categories.name),'') as categories"),
                DB::raw("case when " . $dst . " then calendars.dst_routine else calendars.routine end as routine")
            )
//            ->skip($skip)
//            ->take($per_page)
            ->groupBy('offers.id')
            ->orderBy("offers.updated_at", "desc")
            ->get();
        return ErrorCode::success($offers);
    }

    public function getOffer(Request $request, $offer_id)
    {
        $token = $request->user->token;
        $offer = Offer::getOfferDetail($offer_id, null,$token);

        if (empty($offer)) {
            return ErrorCode::errorNotExist('offer');
        }
//        echo json_encode($offer);
        return ErrorCode::success($offer);
    }

    public function companyGetOffer(Request $request, $offer_id)
    {
        $token = $request->user->token;
        $company_id = $request->user->company_id;
        $offer = Offer::getOfferDetail($offer_id, $company_id,$token);

        if (empty($offer)) {
            return ErrorCode::errorNotExist('offer');
        }
//        echo json_encode($offer);
        return ErrorCode::success($offer);
    }

    public function addOffer(Request $request)
    {
        $company_id = Input::get('company_id', null);
        $token = $request->user->token;
        $param = Input::get('param', null);
        if (is_null($param) || is_null($company_id)) {
            return ErrorCode::errorMissingParam();
        }
        try {
            return ErrorCode::success(Offer::insertOffer($param, $company_id, $token));
        } catch (\Exception $ex) {
            return $ex->getMessage();
        }
    }

    public function companyAddOffer(Request $request)
    {
        $company_id = $request->user->company_id;
        $token = $request->user->token;
        $param = Input::get('param', null);
        if (is_null($param)) {
            return ErrorCode::errorMissingParam();
        }

        try {
            return ErrorCode::success(Offer::insertOffer($param, $company_id, $token));
        } catch (\Exception $ex) {
            return $ex->getMessage();
        }
    }

    public function companyUpdateOffer(Request $request, $offer_id)
    {
        $company_id = $request->user->company_id;
        $token = $request->user->token;
        $param = Input::get('param', null);
        if (is_null($param)) {
            return ErrorCode::errorMissingParam();
        }

        try {
            return ErrorCode::success(Offer::updateOfferInfo($param, $offer_id, $company_id, $token));
        } catch (\Exception $ex) {
            return $ex->getMessage();
        }
    }

    public function updateOffer(Request $request, $offer_id)
    {
        $token = $request->user->token;
        $company_id = Input::get('company_id', null);
        $param = Input::get('param', null);
        if (is_null($param) || is_null($company_id)) {
            return ErrorCode::errorMissingParam();
        }
        try {
            return ErrorCode::success(Offer::updateOfferInfo($param, $offer_id, $company_id, $token));
        } catch (\Exception $ex) {
            return $ex->getMessage();
        }
    }

    public function deleteOffer($offer_id)
    {
        try {
            $result = DB::transaction(function () use ($offer_id) {
                $offer = Offer::where('offers.id', $offer_id)->first();
                if (empty($offer)) {
                    throw new \Exception(ErrorCode::errorNotExist('offer'));
                }
                $offer->delete();
                OfferOption::where('offer_options.offer_id', $offer_id)->delete();
                OfferDriverCar::where('offer_driver_cars.offer_id', $offer_id)->delete();
                Calendar::where([['calendars.owner_id', $offer_id], ['calendars.type', Calendar::OFFER_TYPE]])->delete();
                return null;
            });
            if (is_null($result)) {
                return ErrorCode::success('success');
            }
        } catch (\Exception $ex) {
            return ErrorCode::errorDB();
        }
    }

    public function companyDeleteOffer(Request $request, $offer_id)
    {
        $company_id = $request->user->company_id;
        try {
            $result = DB::transaction(function () use ($company_id, $offer_id) {
                $offer = Offer::where('offers.id', $offer_id)->where('company_id', $company_id)->first();
                if (empty($offer)) {
                    throw new \Exception(ErrorCode::errorNotExist('offer'));
                }
                $offer->delete();
                OfferOption::where('offer_options.offer_id', $offer_id)->delete();
                OfferDriverCar::where('offer_driver_cars.offer_id', $offer_id)->delete();
                Calendar::where([['calendars.owner_id', $offer_id], ['calendars.type', Calendar::OFFER_TYPE]])->delete();
                return null;
            });
            if (is_null($result)) {
                return ErrorCode::success('success');
            } else {
                return ErrorCode::errorDB();
            }
        } catch (\Exception $ex) {
            return ErrorCode::errorDB();
        }
    }


    public function getBookingOfferInfo(Request $request, $booking_id)
    {
        $company_id = $request->user->company_id;
        $token = $request->user->token;
        //1.查出booking
        $booking = Booking::where([["id", $booking_id],
            ["company_id", $company_id]])->first();
        if (empty($booking)) {
            return ErrorCode::errorNotExist("booking ");
        }
        //2.查出booking对应offer
        $offer = Offer::where([["id", $booking->offer_id],
            ["company_id", $company_id]])
            ->select('id as offer_id', 'cost_min', 'price', 'tva', 'calc_method', 'pre_time')
            ->first();


        if (empty($offer)) {
            return ErrorCode::errorOfferHaveBeenRemoved();
        }
        //3.查出offer对应car和司机  并  将当前司机合并入相应的car中
        $booking_car = json_decode($booking->car_data, true);
        $booking_driver = json_decode($booking->driver_data, true);
        $offer_car_categories = $this->getOfferCars($company_id, $token, $offer);
        $markDriver = 0;
        $calendarController = new CalendarsController();
//        echo $offer_car_categories;
        foreach ($offer_car_categories as $offer_car_category) {

            $cars = $offer_car_category->cars;

            foreach ($cars as $car) {
                if ($markDriver == 0 && $car->car_id == $booking_car['car_id']) {
                    $markDriver = 1;
                }
                $appointed_time = strtotime($booking->preparation_time) + $offer->pre_time * 60;

//                echo "<p> appointed time is <p>".$booking->preparation_time."timestamp is ".strtotime($booking->preparation_time);
                if (!$calendarController->
                checkCarSpecifiedTimeAvailable($car->car_id, $appointed_time,
                    $offer->pre_time, $booking->estimate_time)
                ) {
                    if ($car->car_id != $booking_car['car_id']) {
                        $key = $cars->search($car);
                        $cars->pull($key);
                        continue;
                    }

                }
                $drivers = $this->getOfferDrivers($offer, $car->car_id, $token);
                foreach ($drivers as $driver) {
                    if ($markDriver == 1 && $driver->driver_id == $booking_driver['driver_id']) {
                        $markDriver = 2;
                        continue;
                    }
                    if (!$calendarController->checkDriverSpecifiedTimeAvailable($driver->driver_id,
                        $appointed_time,
                        $offer->pre_time, $booking->estimate_duration)
                    ) {
                        $key = $drivers->search($driver);
                        $drivers->pull($key);
//                        echo "<p>driver has been removed <p><br>".$driver."<br>";
                        continue;
                    }
                }
                if ($markDriver == 1) {
                    $markDriver = 2;
                    $driver = Driver::leftjoin('users', 'drivers.user_id', '=', 'users.id')
                        ->where('drivers.id', $booking_driver['driver_id'])
                        ->select('drivers.id as driver_id',
                            'drivers.license_number',
                            'drivers.hidden_last',
                            'users.first_name',
                            'users.last_name',
                            'users.gender',
                            'users.mobile', 'users.email',
                            DB::raw(UrlSpell::getUrlSpell()->getSpellAvatarInDB('users.updated_at', 'users.avatar_url', 'offer_driver_cars.driver_id', $token, UrlSpell::companyDriverType) . " as avatar_url", ''))
                        ->first();
                    $drivers->push($driver);
                }
                if ($drivers->count() == 0) {
                    $key = $cars->search($car);
                    $cars->pull($key);
//                    echo "<p> car has been removed <p><br>".$car."<br>";
                    continue;
                }
                $car->drivers = $drivers->every(1);

            }
            if ($markDriver == 0 && $offer_car_category->category_id == $booking_car['car_category_id']) {
                $car = OfferDriverCar::leftjoin('cars', 'cars.id', '=', 'offer_driver_cars.car_id')
                    ->where('offer_driver_cars.offer_id', $offer->offer_id)
                    ->leftjoin('car_models', 'car_models.id', '=', 'cars.car_model_id')
                    ->leftjoin('car_brands', 'car_brands.id', '=', 'car_models.car_brand_id')
                    ->leftjoin('car_categories', 'car_categories.id', '=', 'car_models.car_category_id')
                    ->where('car_models.car_category_id', $offer_car_category->category_id)
                    ->where('car_models.car_category_id', $booking_car['car_id'])
                    ->select('offer_driver_cars.car_id', 'car_models.name as model',
                        'cars.bags_max',
                        'cars.seats_max',
                        'cars.license_plate as license_plate',
                        'cars.year as year',
                        'cars.color as color',
                        'car_brands.name as brand',
                        'car_models.id as car_model_id',
                        'car_brands.id as car_brand_id',
                        'car_categories.id as car_category_id',
                        UrlSpell::getUrlSpell()->getCarsImgInDB($company_id, $token))
                    ->groupBy('cars.id')
                    ->first();
                $cars->push($car);
            }
            $cars->every(1);
            if ($cars->count() == 0) {
                $carsKey = $offer_car_categories->search($offer_car_category);
                $offer_car_categories->pull($carsKey);
            }


            $offer_car_category->cars = $cars;
        }

        $offer->car_categories = $offer_car_categories;

        //4.查出booking对应option
        $this->getOfferOptions($offer);
        return ErrorCode::success($offer);
    }


    private function getOfferDrivers($offer, $car_id, $token)
    {
        $drivers = OfferDriverCar::leftjoin('drivers', 'offer_driver_cars.driver_id', '=', 'drivers.id')
            ->leftjoin('users', 'drivers.user_id', '=', 'users.id')
            ->where('offer_driver_cars.offer_id', $offer->offer_id)
            ->where('offer_driver_cars.car_id', $car_id)
            ->select('offer_driver_cars.driver_id',
                'drivers.license_number',
                'drivers.hidden_last',
                'users.first_name',
                'users.last_name',
                'users.gender',
                'users.mobile', 'users.email',
                DB::raw(UrlSpell::getUrlSpell()->getSpellAvatarInDB('users.updated_at', 'users.avatar_url', 'offer_driver_cars.driver_id', $token, UrlSpell::companyDriverType) . " as avatar_url", ''))
            ->get();
        return $drivers;
    }

    private function getOfferCars($company_id, $token, $offer, $car_category_id = 0)
    {
        if ($car_category_id == 0) {
            $carCategories = OfferDriverCar::leftjoin('cars', 'cars.id', '=', 'offer_driver_cars.car_id')
                ->leftjoin('car_models', 'car_models.id', '=', 'cars.car_model_id')
                ->leftjoin('car_categories', 'car_categories.id', '=', 'car_models.car_category_id')
                ->where('offer_driver_cars.offer_id', $offer->offer_id)
                ->select('car_categories.id as category_id',
                    'car_categories.name as category')
                ->groupBy('car_categories.id')
                
                ->get();
        } else {
            $carCategories = OfferDriverCar::leftjoin('cars', 'cars.id', '=', 'offer_driver_cars.car_id')
                ->leftjoin('car_models', 'car_models.id', '=', 'cars.car_model_id')
                ->leftjoin('car_categories', 'car_categories.id', '=', 'car_models.car_category_id')
                ->where('offer_driver_cars.offer_id', $offer->offer_id)
                ->where('car_categories.id', $car_category_id)
                ->select('car_categories.id as category_id',
                    'car_categories.name as category')
                ->groupBy('car_categories.id')
                ->get();
        }
        foreach ($carCategories as $carCategory) {
            $cars = OfferDriverCar::leftjoin('cars', 'cars.id', '=', 'offer_driver_cars.car_id')
                ->where('offer_driver_cars.offer_id', $offer->offer_id)
                ->leftjoin('car_models', 'car_models.id', '=', 'cars.car_model_id')
                ->leftjoin('car_brands', 'car_brands.id', '=', 'car_models.car_brand_id')
                ->leftjoin('car_categories', 'car_categories.id', '=', 'car_models.car_category_id')
                ->where('car_models.car_category_id', $carCategory->category_id)
                ->select('offer_driver_cars.car_id', 'car_models.name as model',
                    'cars.bags_max',
                    'cars.seats_max',
                    'cars.license_plate as license_plate',
                    'cars.year as year',
                    'cars.color as color',
                    'car_brands.name as brand',
                    'car_models.id as car_model_id',
                    'car_brands.id as car_brand_id',
                    'car_categories.id as car_category_id',
                    UrlSpell::getUrlSpell()->getCarsImgInDB($company_id, $token))
                ->groupBy('cars.id')
                ->get();
//            echo $cars."<br>";
            $carCategory->cars = $cars;
        }
        return $carCategories;
    }

    private function getOfferOptions($offer)
    {
        $options = OfferOption::leftjoin('options', 'options.id', '=', 'offer_options.option_id')
            ->where('offer_options.offer_id', $offer->offer_id)
            ->select('options.id as option_id', 'options.add_max',
                'options.price', 'options.title as name',
                'options.type', 'options.parent_id')
            ->get();

        if (!empty($options)) {
            foreach ($options as $option) {
                if ($option->type == 'GROUP') {
                    $childOptions = Option::where('parent_id', $option->option_id)
                        ->select('id as option_id', 'add_max',
                            'price', 'title as name',
                            'type', 'parent_id')
                        ->get();
                    $option->group = $childOptions;
                }
            }
        } else {
        }
        $offer->options = $options;
    }

}
