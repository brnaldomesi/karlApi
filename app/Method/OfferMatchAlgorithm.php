<?php
/**
 * Created by PhpStorm.
 * User: liqihai
 * Date: 16/9/2
 * Time: 上午10:34
 */

namespace App\Method;


use App\Constants;
use App\ErrorCode;
use App\Model\Booking;
use App\Model\Car;
use App\Model\CompanyAnSetting;
use App\Model\Driver;
use App\Model\Offer;
use App\Model\Order;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OfferMatchAlgorithm extends MatchAlgorithm
{

    public function returnServiceCheck($type,$d_lng,$d_lat,$a_lng,$a_lat,$estimate_distance,$unit,
                                       $estimate_duration,$company_id,$d_is_airport,$a_is_airport
    )
    {
        return DB::transaction(function ()use($type,$d_lng,$d_lat,$a_lng,$a_lat,
            $estimate_distance,$unit,$estimate_duration,$company_id,$d_is_airport,$a_is_airport){
            $setting = CompanyAnSetting::where('company_id',$company_id)->first();
            if (!is_numeric($d_lat) || $d_lat > 90 || $d_lat < -90) {
                return ErrorCode::errorParam('d_lat');
            }
            if (!is_numeric($d_lng) || $d_lng > 180 || $d_lng < -180) {
                return ErrorCode::errorParam('d_lat');
            }

            if(!is_numeric($d_is_airport) || ($d_is_airport!=Offer::IS_AIRPORT && $d_is_airport !=Offer::NOT_AIRPORT)){
                return ErrorCode::errorParam('d_is_airport');
            }
            if(!is_numeric($a_is_airport) || ($a_is_airport!=Offer::IS_AIRPORT && $a_is_airport !=Offer::NOT_AIRPORT)){
                return ErrorCode::errorParam('a_is_airport');
            }
            if ($type == Booking::CHECK_TYPE_DISTANCE) {
                if (!is_numeric($a_lat) || $a_lat > 90 || $a_lat < -90) {
                    return ErrorCode::errorParam('a_lat');
                }
                if (!is_numeric($a_lng) || $a_lng > 180 || $a_lng < -180) {
                    return ErrorCode::errorParam('a_lat');
                }
                if (!is_numeric($estimate_distance) || $estimate_distance < 0) {
                    return ErrorCode::errorParam('estimate_distance');
                }
                $offerCount = count($this->offerP2PMatch(
                    $company_id,$unit,
                    $d_lat,$d_lng,$a_lat,$a_lng,
                    $estimate_distance,null,
                    null,
                    self::AN_TYPE_NORMAL,
                    CompanyAnSetting::COMBINE_DISABLE
                    ));
                if($offerCount==0 ){
                    $count = $this->offerLnMatch($company_id,$unit, $d_lat, $d_lng);
                    if ($count > 0) {
                        //1.1.1开启ln查询
                        if ($setting->ln == CompanyAnSetting::LN_ENABLE &&
                            $setting->locked==CompanyAnSetting::AN_UNLOCKED) {
                            $offerCount = count($this->offerP2PMatch(
                                $company_id,$unit,
                                $d_lat, $d_lng, $a_lat, $a_lng,
                                $estimate_distance, null,
                                null, self::AN_TYPE_LN,
                                $setting->combine));
                        }
                    } //1.2不在local,判断gn
                    else {
                        //1.2.2 gn开启
                        if ($setting->gn == CompanyAnSetting::GN_ENABLE &&
                            $setting->locked==CompanyAnSetting::AN_UNLOCKED) {
                            $offerCount = count($this->offerP2PMatch(
                                $company_id,$unit,
                                $d_lat, $d_lng, $a_lat, $a_lng,
                                $estimate_distance, null,
                                null,
                                self::AN_TYPE_GN,
                                $setting->combine));
                        }
                    }
                }
            } elseif ($type == Booking::CHECK_TYPE_HOURLY) {
                if (!is_numeric($estimate_duration) || $estimate_duration < 0) {
                    return ErrorCode::errorParam('estimate_duration');
                }
                $offerCount = count($this->offerHourlyMatch(
                    $company_id,$unit,
                    $d_lat, $d_lng,
                    $estimate_duration,
                    null,
                    null,
                    self::AN_TYPE_NORMAL,
                    CompanyAnSetting::COMBINE_DISABLE));
                //1.公司offer为空,查看是否在local
                if ($offerCount == 0) {
                    //1.1在local
                    $count = $this->offerLnMatch($company_id,$unit, $d_lat, $d_lng);
                    if ($count > 0) {
                        //1.1.1开启ln查询
                        if ($setting->ln == CompanyAnSetting::LN_ENABLE &&
                            $setting->locked==CompanyAnSetting::AN_UNLOCKED) {
                            $offerCount = count($this->offerHourlyMatch(
                                $company_id,$unit,
                                $d_lat, $d_lng,
                                $estimate_duration,
                                null,
                                null,
                                self::AN_TYPE_LN,
                                $setting->combine));
                        }
                    } //1.2不在local,判断gn
                    else {
                        //1.2.2 gn开启
                        if ($setting->gn == CompanyAnSetting::GN_ENABLE &&
                            $setting->locked==CompanyAnSetting::AN_UNLOCKED) {
                            $offerCount = count($this->offerHourlyMatch(
                                $company_id,$unit,
                                $d_lat, $d_lng,
                                $estimate_duration,
                                null,
                                null,
                                self::AN_TYPE_GN,
                                CompanyAnSetting::COMBINE_DISABLE));
                        }
                    }
                }


            } else {
                return ErrorCode::errorParam('type');
            }
            if ($offerCount > 0) {
                return ErrorCode::success($offerCount);
            } else {
                return ErrorCode::successEmptyResult('no offer to match');
            }
        });
    }


    /**
     * 1.查询公司offer
     * 2.如果没有查询GN
     * 3.如果有继续
     *
     * @param $company_id
     * @param $unit
     * @param $d_lat
     * @param $d_lng
     * @param $a_lat
     * @param $a_lng
     * @param $estimate_distance
     * @param $estimate_duration
     * @param $car_category_id
     * @param $appointed_time
     * @param $token
     * @return array
     * @throws \Exception
     */
    public function offerP2PSearch($company_id,$unit,
                                   $d_lat, $d_lng, $a_lat, $a_lng,
                                   $estimate_distance, $estimate_duration,
                                   $car_category_id, $appointed_time, $token)
    {

        //先匹配公司offer
        $offers = $this->offerP2PMatch(
            $company_id,$unit,
            $d_lat, $d_lng, $a_lat, $a_lng,
            $estimate_distance, $appointed_time,
            null,
            self::AN_TYPE_NORMAL,
            CompanyAnSetting::COMBINE_DISABLE
            );
        $setting = CompanyAnSetting::where("company_id", $company_id)->first();
//        echo $setting;
        //1.公司offer为空,查看是否在local
        if (count($offers) == 0) {
            //1.1在local
            $count = $this->offerLnMatch($company_id, $unit,$d_lat, $d_lng);
            if ($count > 0) {
                //1.1.1开启ln查询
                if ($setting->ln == CompanyAnSetting::LN_ENABLE &&
                    $setting->locked==CompanyAnSetting::AN_UNLOCKED) {
                    $offers = $this->offerP2PMatch(
                        $company_id,$unit,
                        $d_lat, $d_lng, $a_lat, $a_lng,
                        $estimate_distance, $appointed_time,
                        null,
                        self::AN_TYPE_LN,
                        $setting->combine);
                    if (count($offers) == 0) {
                        throw new \Exception(ErrorCode::errorOffersHasNoOffer());
                    } else {
                        return $this->checkOfferAvailable($company_id, $offers, $appointed_time,
                            $d_lat, $d_lng, $a_lat, $a_lng,
                            $estimate_duration,
                            $estimate_distance, $car_category_id, $token,$unit);
                    }
                } else {
                    //1.1.2关闭ln返回null
                    throw new \Exception(ErrorCode::errorOffersHasNoOffer());
                }
            } //1.2不在local,判断gn
            else {
                //1.2.2 gn开启
                if ($setting->gn == CompanyAnSetting::GN_ENABLE &&
                    $setting->locked==CompanyAnSetting::AN_UNLOCKED) {
                    $offers = $this->offerP2PMatch(
                        $company_id,$unit,
                        $d_lat, $d_lng, $a_lat, $a_lng,
                        $estimate_distance, $appointed_time,
                        null,
                        self::AN_TYPE_GN,
                        CompanyAnSetting::COMBINE_DISABLE);
                    if (count($offers) == 0) {
                        throw new \Exception(ErrorCode::errorOffersHasNoOffer());
                    } else {
                        return $this->checkOfferAvailable($company_id, $offers, $appointed_time,
                            $d_lat, $d_lng, $a_lat, $a_lng,
                            $estimate_duration,
                            $estimate_distance, $car_category_id, $token,$unit);
                    }
                } else {
                    throw new \Exception(ErrorCode::errorOffersHasNoOffer());
                }
            }
        } //1.2公司offer不为空
        else {
            //1.2.1判断是否有车和司机
            $offerResult = $this->checkOfferAvailable($company_id, $offers, $appointed_time,
                $d_lat, $d_lng, $a_lat, $a_lng,
                $estimate_duration,
                $estimate_distance, $car_category_id, $token,$unit);
            //1.2.1.1有车和司机
            if (count($offerResult['offer']) > 0) {
                //1.2.1.1.1判断是否开启ln及combine
                if ($setting->ln == CompanyAnSetting::LN_ENABLE &&
                    $setting->combine == CompanyAnSetting::COMBINE_ENABLE &&
                    $setting->locked==CompanyAnSetting::AN_UNLOCKED
                ) {
                    $offers = $this->offerP2PMatch(
                        $company_id,$unit,
                        $d_lat, $d_lng, $a_lat, $a_lng,
                        $estimate_distance, $appointed_time,
                        null,
                        self::AN_TYPE_LN,
                        $setting->combine);
                    if (count($offers) == 0) {
                        return $offerResult;
                    } else {
                        $combine = $this->checkOfferAvailable($company_id, $offers, $appointed_time,
                            $d_lat, $d_lng, $a_lat, $a_lng,
                            $estimate_duration,
                            $estimate_distance, $car_category_id,
                            $token,$unit, $offerResult['offer'],
                            $offerResult['offerError'],
                            $offerResult['carModels']);
                        return $combine;
                    }
                } else {
                    return $offerResult;
                }
            } //1.2.1.2没有有车和司机
            else {
                //1.2.1.2.1 是否开启ln
                if ($setting->ln == CompanyAnSetting::LN_ENABLE &&
                    $setting->locked==CompanyAnSetting::AN_UNLOCKED) {
                    $offers = $this->offerP2PMatch(
                        $company_id,$unit,
                        $d_lat, $d_lng, $a_lat, $a_lng,
                        $estimate_distance, $appointed_time,
                        null, self::AN_TYPE_LN, $setting->combine);
                    if (count($offers) == 0) {
                        throw new \Exception(ErrorCode::errorOffersHasNoOffer());
                    } else {
                        $offersResult =  $this->checkOfferAvailable($company_id, $offers, $appointed_time,
                            $d_lat, $d_lng, $a_lat, $a_lng,
                            $estimate_duration,
                            $estimate_distance, $car_category_id, $token,$unit);
                        return $offersResult;
                    }
                } else {
                    return $offerResult;
                }
            }
        }

    }


    /**
     * 1.查询公司offer
     * 2.如果没有查询GN
     * 3.如果有继续
     * @param $company_id
     * @param $unit
     * @param $d_lat
     * @param $d_lng
     * @param $estimate_duration
     * @param $car_category_id
     * @param $appointed_time
     * @param $token
     * @return array
     * @throws \Exception
     */
    public function offerHourlySearch($company_id, $unit,$d_lat, $d_lng,
                                      $estimate_duration, $car_category_id,
                                      $appointed_time, $token)
    {

        $offers = $this->offerHourlyMatch(
            $company_id,$unit,
            $d_lat, $d_lng,
            $estimate_duration,
            $appointed_time,
            null,
            self::AN_TYPE_NORMAL,
            CompanyAnSetting::COMBINE_DISABLE
        );

        $setting = CompanyAnSetting::where("company_id", $company_id)->first();
        //1.公司offer为空,查看是否在local
        if (count($offers) == 0) {
            //1.1在local
            $count = $this->offerLnMatch($company_id,$unit, $d_lat, $d_lng);
            if ($count > 0) {
                //1.1.1开启ln查询
                if ($setting->ln == CompanyAnSetting::LN_ENABLE &&
                    $setting->locked==CompanyAnSetting::AN_UNLOCKED) {
                    $offers = $this->offerHourlyMatch(
                        $company_id,$unit,
                        $d_lat, $d_lng,
                        $estimate_duration,
                        $appointed_time,
                        null,
                        self::AN_TYPE_LN,
                        $setting->combine);
                    if (count($offers) == 0) {
                        throw new \Exception(ErrorCode::errorOffersHasNoOffer());
                    } else {
                        return $this->checkOfferAvailable($company_id, $offers, $appointed_time,
                            $d_lat, $d_lng, $d_lat, $d_lng,
                            $estimate_duration, 0, $car_category_id, $token,$unit);
                    }
                } else {
                    //1.1.2关闭ln返回null
                    throw new \Exception(ErrorCode::errorOffersHasNoOffer());
                }
            } //1.2不在local,判断gn
            else {
                //1.2.2 gn开启
                if ($setting->gn == CompanyAnSetting::GN_ENABLE &&
                    $setting->locked==CompanyAnSetting::AN_UNLOCKED) {
                    $offers = $this->offerHourlyMatch(
                        $company_id,$unit,
                        $d_lat, $d_lng,
                        $estimate_duration,
                        $appointed_time,
                        null,
                        self::AN_TYPE_GN,
                        CompanyAnSetting::COMBINE_DISABLE);
                    if (count($offers) == 0) {
                        throw new \Exception(ErrorCode::errorOffersHasNoOffer());
                    } else {
                        return $this->checkOfferAvailable($company_id, $offers, $appointed_time,
                            $d_lat, $d_lng, $d_lat, $d_lng,
                            $estimate_duration, 0, $car_category_id, $token,$unit);
                    }
                } else {
                    throw new \Exception(ErrorCode::errorOffersHasNoOffer());
                }
            }
        } //1.2公司offer不为空
        else {
            //1.2.1判断是否有车和司机
            $offerResult = $this->checkOfferAvailable($company_id, $offers, $appointed_time,
                $d_lat, $d_lng, $d_lat, $d_lng,
                $estimate_duration, 0, $car_category_id, $token,$unit);
            //1.2.1.1有车和司机
            if (count($offerResult['offer']) > 0) {
                //1.2.1.1.1判断是否开启ln及combine
                if ($setting->ln == CompanyAnSetting::LN_ENABLE &&
                    $setting->combine == CompanyAnSetting::COMBINE_ENABLE &&
                    $setting->locked==CompanyAnSetting::AN_UNLOCKED
                ) {
                    $offers = $this->offerHourlyMatch(
                        $company_id,$unit,
                        $d_lat, $d_lng,
                        $estimate_duration,
                        $appointed_time,
                        null,
                        self::AN_TYPE_LN,
                        $setting->combine);
                    if (count($offers) == 0) {
                        return $offerResult;
                    } else {
                        $combine = $this->checkOfferAvailable($company_id, $offers, $appointed_time,
                            $d_lat, $d_lng, $d_lat, $d_lng,
                            $estimate_duration, 0, $car_category_id, $token,$unit, $offerResult['offer'],
                            $offerResult['offerError'],$offerResult['carModels']);
                        return $combine;
                    }
                } else {
                    return $offerResult;
                }
            } //1.2.1.2没有有车和司机
            else {
                //1.2.1.2.1 是否开启ln
                if ($setting->ln == CompanyAnSetting::LN_ENABLE &&
                    $setting->locked==CompanyAnSetting::AN_UNLOCKED) {
                    $offers = $this->offerHourlyMatch(
                        $company_id,$unit,
                        $d_lat, $d_lng,
                        $estimate_duration,
                        $appointed_time,
                        null,
                        self::AN_TYPE_LN,
                        $setting->combine);
                    if (count($offers) == 0) {
                        throw new \Exception(ErrorCode::errorOffersHasNoOffer());
                    } else {
                        $combine = $this->checkOfferAvailable($company_id, $offers, $appointed_time,
                            $d_lat, $d_lng, $d_lat, $d_lng,
                            $estimate_duration, 0, $car_category_id, $token,$unit, $offerResult['offer'],
                            $offerResult['offerError'],$offerResult['carModels']);

                        return $combine;
                    }
                } else {
                    throw new \Exception(ErrorCode::errorOffersHasNoOffer());
                }
            }
        }
    }

    /**
     * 检查offer是否可用
     * 1.获取offer下的全部汽车和司机，
     * @param $company_id
     * @param $offers
     * @param $appointed_time
     * @param $d_lat
     * @param $d_lng
     * @param $a_lat
     * @param $a_lng
     * @param $estimate_duration
     * @param $estimate_distance
     * @param $car_category_id
     * @param $token
     * @param $unit
     * @param null $tempOffers
     * @param null $tempError
     * @param null $comCar
     * @return array
     */
    private function checkOfferAvailable($company_id, $offers, $appointed_time,
                                         $d_lat, $d_lng, $a_lat, $a_lng,
                                         $estimate_duration, $estimate_distance,
                                         $car_category_id, $token,$unit,
                                         $tempOffers = null, $tempError = null,$comCar=null)

    {

        if (is_null($tempOffers)) {
            $tempOffers = array();
        }
        if (is_null($tempError)) {
            $tempError = ["car" => false];
        }
        $checkCarModel = null;
        foreach ($offers as $offer) {
            $key = intval($offers->search($offer));
            //检查offer是否在routine内
            if (!$this->checkOfferSpecifiedTimeAvailable($offer->offer_id, $appointed_time, $estimate_duration)) {
                $offers->pull($key);
                continue;
            }
            $carCategories = $this->getOfferCars($company_id, $token, $offer,$appointed_time ,
                $car_category_id , ($company_id==$offer->company_id?null:$comCar));
//            echo "<br>offer in ".$offer->offer_id." cars is ".$carCategories."<br><br>";
            foreach ($carCategories as $carCategory) {

                $cars = $carCategory->cars;

                $realCars = array();
                $carModel = [];
                foreach ($cars as $car) {
                    if (
                        !$this->checkCarSpecifiedTimeAvailable($car->car_id, $appointed_time, $estimate_duration) ||
                        !$this->bookingMatch($car->pre_time, 'car', $car->car_id, $appointed_time, $estimate_duration, $d_lat, $d_lng, $a_lat, $a_lng)
                    ) {
                        $key = intval($cars->search($car));
                        $cars->pull($key);
                        continue;
                    } else {
                        if (!$tempError['car']) {
                            $tempError['car'] = true;
                        }
                        $drivers = $this->getOfferDrivers($company_id, $offer, $car->car_id, $appointed_time,$token);

                        foreach ($drivers as $driver) {
                            if (
                                !$this->bookingMatch($car->pre_time, 'driver', $driver->driver_id, $appointed_time, $estimate_duration, $d_lat, $d_lng, $a_lat, $a_lng) ||
                                !$this->checkDriverSpecifiedTimeAvailable($driver->driver_id, $appointed_time, $estimate_duration)
                            ) {
                                $key = intval($drivers->search($driver));
                                $drivers->pull($key);
                                continue;
                            }
                        }
                        if ($drivers->count() == 0) {
                            $key = intval($cars->search($car));
                            $cars->pull($key);
                            continue;
                        } else {
                            //nothing to do
                        }
                        $car->drivers = $drivers->every(1);
                    }


                    if($company_id == $offer->company_id){
                        if(is_null($checkCarModel)){
                            $checkCarModel = array();
                        }
                        array_push($checkCarModel,$car->car_model_id);
                    }

                    $searchFlag = false;
                    for($i = 0; $i < count($carModel); $i ++)
                    {
                        if($carModel[$i] == $car->car_model_id)
                        {
                            $searchFlag = true;
                            break;
                        }
                    }

                    if($searchFlag == true)
                    {

                    }else
                    {
                        array_push($realCars, $car);
                    }
                }


                $carCategory->cars = $realCars;
                if (count($realCars) == 0) {
                    $carsKey = intval($carCategories->search($carCategory));
                    $carCategories->pull($carsKey);
                }
            }
            if ($carCategories->count() == 0) {
                $offers->pull($key);
                continue;
            }


            $offer->car_categories = $carCategories->every(1);
            $min_cost = $offer->cost_min;
            $price = $offer->prices;
            $calc_method = $offer->calc_method;
            $offer->basic_cost = PaymentMethod::offerPriceSettlement($min_cost, $calc_method,
                $estimate_duration, $estimate_distance, $price,$unit,$offer->unit);
            $offer->tva_cost = round((1 + $offer->tva / 100) * $offer->basic_cost, 2);
            $this->getOfferOptions($offer);
            array_push($tempOffers, $offer);
        }
        return ["offer" => $tempOffers, "offerError" => $tempError,"carModels"=>$checkCarModel];
    }

    public function checkCustomerQuote($company_id, $token, $appointed_time,
                                       $estimate_duration, $delay_time,
                                       $a_lat, $a_lng, $d_lat, $d_lng)
    {
        Log::info("start check time is ".time());
        $drivers = Driver::leftjoin('users', 'drivers.user_id', '=', 'users.id')
            ->where('users.company_id', $company_id)
            ->where('drivers.delay_time', '<', $delay_time)
            ->select('drivers.id as driver_id',
                'drivers.license_number',
                'drivers.hidden_last',
                'users.first_name',
                'users.last_name',
                'users.gender',
                'users.mobile',
                'users.email',
                DB::raw(UrlSpell::getUrlSpell()->getSpellAvatarInDB('users.updated_at', 'users.avatar_url', 'drivers.id', $token, UrlSpell::companyDriverType) . " as avatar_url", ''))
            ->get();
        if($drivers->count() == 0){
            return ErrorCode::errorDriverUseAppointedTime();
        }

        foreach ($drivers as $driver) {
            if(!$this->checkDriverSpecifiedTimeAvailable($driver->driver_id, $appointed_time, $estimate_duration)){
                $key = intval($drivers->search($driver));
                $drivers->pull($key);
            }
            $driver->pre_time = $this->getDriverMinPreTime($driver->driver_id,$appointed_time,$estimate_duration,$d_lat, $d_lng,$a_lat, $a_lng);
        }

        $drivers = $drivers->every(1);

        $cars = Car::leftjoin('car_models', 'car_models.id', '=', 'cars.car_model_id')
            ->leftjoin('car_brands', 'car_brands.id', '=', 'car_models.car_brand_id')
            ->leftjoin('car_categories', 'car_categories.id', '=', 'car_models.car_category_id')
            ->where('cars.company_id', $company_id)
            ->where("cars.pre_time","<=",$delay_time)
            ->select('cars.id as car_id', 'car_models.name as model',
                'cars.bags_max',
                'cars.seats_max',
                'cars.license_plate as license_plate',
                'cars.year as year',
                'cars.pre_time',
                'cars.color as color',
                'car_categories.id as category_id',
                'car_categories.name as category',
                'car_brands.name as brand',
                'car_models.id as car_model_id',
                'car_brands.id as car_brand_id',
                UrlSpell::getUrlSpell()->getCarsImgInDB($company_id, $token))
            ->orderBy('car_categories.name')
            ->get();

        foreach ($cars as $car) {
            $tempDrivers = clone $drivers;
            foreach ($tempDrivers as $tempDriver) {
                if($car->pre_time > $tempDriver->pre_time){
                    $key = intval($tempDrivers->search($tempDriver));
                    $tempDrivers->pull($key);
                    continue;
                }
            }
            $tempDrivers = $tempDrivers->every(1);
            if ($tempDrivers->count()==0||
                !$this->bookingMatch($car->pre_time, 'car', $car->car_id, $appointed_time, $estimate_duration, $a_lat, $a_lng, $d_lat, $d_lng) ||
                !$this->checkCarSpecifiedTimeAvailable($car->car_id, $appointed_time, $estimate_duration)
            ) {
                $key = intval($cars->search($car));
                $cars->pull($key);
                continue;
            }
            $car->drivers=$tempDrivers;
        }
        $cars = $cars->every(1);
        if($cars->count() == 0){
            return ErrorCode::errorCarUseAppointedTime();
        }
        $categories = array();
        foreach ($cars as $car) {
            $category = isset($categories[$car->category_id])?$categories[$car->category_id]:array("category_id"=>$car->category_id,"category"=>$car->category,"cars"=>array());
            array_push($category['cars'],$car);
            $categories[$car->category_id] = $category;
        }
        Log::info("end check time is ".time());

        return ErrorCode::success(array_values($categories));
    }

    private function getDriverMinPreTime($driverId,$appointed_time, $estimate_duration,$d_lat,$d_lng,$a_lat,$a_lng)
    {
        $orderState = [
            Order::ORDER_STATE_ADMIN_CANCEL,
            Order::ORDER_STATE_SUPER_ADMIN_CANCEL,
            Order::ORDER_STATE_PASSENGER_CANCEL,
            Order::ORDER_STATE_TIMES_UP_CANCEL,
            Order::ORDER_STATE_WAIT_DETERMINE,
        ];
        $bookBefore = Booking::leftJoin('orders','orders.booking_id','=','bookings.id')
            ->whereRaw("unix_timestamp(bookings.appointed_at)<={$appointed_time}")
            ->where("bookings.driver_id" , $driverId)
            ->whereNotIn('orders.order_state',$orderState)
            ->select(
                "bookings.id",
                DB::raw("unix_timestamp(bookings.appointed_at) + bookings.estimate_time * 60 AS end_time"),
                DB::raw("CASE WHEN bookings.a_lat IS NULL
                        THEN bookings.d_lat
                      ELSE bookings.a_lat END AS a_lat"),
                DB::raw("CASE WHEN bookings.a_lng IS NULL
                        THEN bookings.d_lng
                      ELSE bookings.a_lng END AS a_lng")
            )
            ->orderBy('bookings.appointed_at','desc')
            ->first();
        $bookAfter =  Booking::leftJoin('orders','orders.booking_id','=','bookings.id')
            ->whereRaw("unix_timestamp(bookings.appointed_at)>={$appointed_time}")
            ->where("bookings.driver_id",$driverId)
            ->whereNotIn('orders.order_state',$orderState)
            ->select(
                DB::raw("unix_timestamp(bookings.appointed_at) AS appointed_at"),
                "bookings.id",
                "bookings.d_lat",
                "bookings.d_lng",
                "bookings.car_data"
            )
            ->orderBy('bookings.appointed_at','asc')
            ->first();
        $preTimeBefore = $appointed_time-time();
        if(!empty($bookBefore)){
            $beforeTime = $this->distanceAlgorithm($bookBefore->a_lng, $bookBefore->a_lat, $d_lng, $d_lat);
            $preTimeBefore = $appointed_time - $bookBefore->end_time - $beforeTime;
        }
        if(!empty($bookAfter)){
            $pre_time = json_decode($bookAfter->car_data)->pre_time;
            $afterTime = $this->distanceAlgorithm($a_lng, $a_lat, $bookAfter->d_lng, $bookAfter->d_lat);
            if ($appointed_time + ($estimate_duration +$pre_time)* Constants::MINUTE + $afterTime > $bookAfter->appointed_at) {
                $preTimeBefore=0;
            }
        }
        return $preTimeBefore/60;
    }

}