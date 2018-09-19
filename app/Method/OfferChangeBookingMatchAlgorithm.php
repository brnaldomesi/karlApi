<?php
/**
 * Created by PhpStorm.
 * User: liqihai
 * Date: 16/9/3
 * Time: 上午10:08
 */

namespace App\Method;


use App\Model\CompanyAnSetting;
use App\Model\Driver;
use App\Model\OfferDriverCar;
use Illuminate\Support\Facades\DB;

class OfferChangeBookingMatchAlgorithm extends MatchAlgorithm
{
    protected function matchBookingOfferP2PMatch($companyId, $exeComId,$unit,$isReject,
                                                 $dLat, $dLng, $dIsAirport,
                                                 $aLat, $aLng, $aIsAirport,
                                                 $appointedAt, $estDuration, $estDistance,
                                                 $checkCarPass, $checkDriverPass,
                                                 $bookingId, $bookDriverId, $bookCar, $curCost,
                                                 $anSetting, $isAnBill, $token,$ccy
    )
    {
        $offers = $this->offerP2PMatch($companyId,$unit,
            $dLat, $dLng,$aLat,$aLng,
            $estDistance, $appointedAt);
        $offerResult = $this->checkOfferDriverAndCar($companyId, $unit,$offers,
            $appointedAt, $estDuration, $estDistance,
            $dLat, $dLng, $dIsAirport,
            $aLat, $aLng, $aIsAirport,
            $checkCarPass, $checkDriverPass,
            $bookingId, $bookDriverId, $bookCar,
            null,
            $curCost, $token,$ccy
        );
        if ($isAnBill && $isReject) {
            $countAn = $this->offerLnMatch($companyId, $unit,$dLat, $dLng);
            if($countAn>0){
                if ($anSetting->ln == CompanyAnSetting::LN_ENABLE &&
                    $anSetting->locked == CompanyAnSetting::AN_UNLOCKED
                ) {
                    if (count($offerResult['offer']) > 0) {
                        if ($anSetting->combine == CompanyAnSetting::COMBINE_ENABLE) {
                            $offers = $this->offerP2PMatch(
                                $companyId,$unit,
                                $dLat, $dLng, $aLat, $aLng,
                                $estDistance, $appointedAt,
                                null, self::AN_TYPE_LN, $anSetting->combine, $exeComId
                            );
                        } else {
                            $offers = array();
                        }
                    } else {
                        $offers = $this->offerP2PMatch(
                            $companyId,$unit,
                            $dLat, $dLng, $aLat, $aLng,
                            $estDistance, $appointedAt,
                            null,
                            self::AN_TYPE_LN, $anSetting->combine,
                            $exeComId
                        );
                    }
                    $offerResult = $this->checkOfferDriverAndCar($companyId, $unit,$offers,
                        $appointedAt, $estDuration, $estDistance,
                        $dLat, $dLng, $dIsAirport,
                        $aLat, $aLng, $aIsAirport,
                        $checkCarPass, $checkDriverPass,
                        $bookingId, $bookDriverId, $bookCar,
                        $offerResult,
                        $curCost, $token,$ccy);

                }
            }else{
                if ($anSetting->gn == CompanyAnSetting::GN_ENABLE &&
                    $anSetting->locked == CompanyAnSetting::AN_UNLOCKED
                ) {
                    $offers = $this->offerP2PMatch(
                        $companyId,$unit,
                        $dLat, $dLng, $aLat, $aLng,
                        $estDistance, $appointedAt,
                        null, self::AN_TYPE_GN, 0, $exeComId
                    );
                    $offerResult = $this->checkOfferDriverAndCar($companyId,$unit, $offers,
                        $appointedAt, $estDuration, $estDistance,
                        $dLat, $dLng, $dIsAirport,
                        $aLat, $aLng, $aIsAirport,
                        $checkCarPass, $checkDriverPass,
                        $bookingId, $bookDriverId, $bookCar,
                        $offerResult,
                        $curCost, $token,$ccy
                    );
                }
            }
        }

        return $offerResult;
    }

    protected function matchBookingOfferHourlyMatch($companyId, $exeComId,$unit,$isReject,
                                                    $dLat, $dLng, $dIsAirport,
                                                    $appointedAt, $estDuration, $estDistance,
                                                    $checkCarPass, $checkDriverPass,
                                                    $bookingId, $bookDriverId, $bookCar, $curCost,
                                                    $anSetting, $isAnBill, $token,$ccy
    )
    {
        $offers = $this->offerHourlyMatch($companyId,$unit,
            $dLat, $dLng,
            $estDuration, $appointedAt);
        $offerResult = $this->checkOfferDriverAndCar($companyId,$unit, $offers,
            $appointedAt, $estDuration, $estDistance,
            $dLat, $dLng, $dIsAirport,
            $dLat, $dLng, $dIsAirport,
            $checkCarPass, $checkDriverPass,
            $bookingId, $bookDriverId, $bookCar,
            null,
            $curCost, $token,$ccy
        );
        if ($isAnBill && $isReject) {
            $countAn = $this->offerLnMatch($companyId,$unit, $dLat, $dLng);
            if ($countAn > 0) {
                if ($anSetting->ln == CompanyAnSetting::LN_ENABLE &&
                    $anSetting->locked == CompanyAnSetting::AN_UNLOCKED
                ) {
                    if (count($offerResult['offer']) > 0) {
                        if ($anSetting->combine == CompanyAnSetting::COMBINE_ENABLE) {
                            $offers = $this->offerHourlyMatch(
                                $companyId,$unit, $dLat, $dLng,
                                $estDuration, $appointedAt,
                                null,
                                self::AN_TYPE_LN, $anSetting->combine,
                                $exeComId
                            );
                        } else {
                            $offers = array();
                        }
                    } else {
                        $offers = $this->offerHourlyMatch(
                            $companyId,$unit, $dLat, $dLng,
                            $estDuration, $appointedAt,
                            null,
                            self::AN_TYPE_LN, $anSetting->combine,
                            $exeComId
                        );
                    }
                    $offerResult = $this->checkOfferDriverAndCar($companyId,$unit, $offers,
                        $appointedAt, $estDuration, $estDistance,
                        $dLat, $dLng, $dIsAirport,
                        $dLat, $dLng, $dIsAirport,
                        $checkCarPass, $checkDriverPass,
                        $bookingId, $bookDriverId, $bookCar,
                        $offerResult,
                        $curCost, $token,$ccy);

                }
            } else {
                if ($anSetting->gn == CompanyAnSetting::GN_ENABLE &&
                    $anSetting->locked == CompanyAnSetting::AN_UNLOCKED
                ) {
                    $offers = $this->offerHourlyMatch(
                        $companyId,$unit, $dLat, $dLng, $estDuration, $appointedAt,
                        null, self::AN_TYPE_GN,
                        CompanyAnSetting::COMBINE_DISABLE, $exeComId
                    );
                    $offerResult = $this->checkOfferDriverAndCar($companyId, $unit,$offers,
                        $appointedAt, $estDuration, $estDistance,
                        $dLat, $dLng, $dIsAirport,
                        $dLat, $dLng, $dIsAirport,
                        $checkCarPass, $checkDriverPass,
                        $bookingId, $bookDriverId, $bookCar,
                        $offerResult,
                        $curCost, $token,$ccy
                    );
                }
            }
        }
        return $offerResult;
    }


    protected function checkOfferDriverAndCar($company_id, $unit,$offers,
                                              $appointed_time, $estimate_duration, $estimate_distance,
                                              $d_lat, $d_lng, $d_is_airport,
                                              $a_lat, $a_lng, $a_is_airport,
                                              $check_car_pass, $check_driver_pass,
                                              $booking_id, $booking_driver_id, $booking_car,
                                              $offerResult,
                                              $currentCost, $token,$ccy)
    {

        if(is_null($offerResult)){
            $offerResult = [
              "offer"=>array(),
              "offerError"=>['car'=>false],
              "carModels"=>array()
            ];
        }

        foreach ($offers as $offer) {
            $key = intval($offers->search($offer));
            if (!$this->checkOfferSpecifiedTimeAvailable($offer->offer_id,
                $appointed_time, $estimate_duration)
                || $offer->ccy != $ccy
            ) {
                $offers->pull($key);
                continue;
            }
            $min_cost = $offer->cost_min;
            $price = $offer->prices;
            $calc_method = $offer->calc_method;
            $offer->basic_cost = PaymentMethod::offerPriceSettlement($min_cost, $calc_method,
                $estimate_duration,
                $estimate_distance,
                $price,
                $unit,$offer->unit,
                $d_is_airport,
                $offer->d_port_price,
                $a_is_airport,
                $offer->a_port_price
            );
            $offer->tva_cost = round((1 + $offer->tva / 100) * $offer->basic_cost, 2);

            if ($offer->tva_cost > $currentCost) {
                continue;
            }

//            echo $offers->count();
            $offer_car_categories = $this->getOfferCars($company_id, $token, $offer, $appointed_time);
            $markDriver = 0;
//        echo $offer_car_categories;
            foreach ($offer_car_categories as $offer_car_category) {

                $cars = $offer_car_category->cars;

                foreach ($cars as $car) {
                    if ($markDriver == 0 && $car->car_id == $booking_car['car_id'] && $check_car_pass) {
                        $markDriver = 1;
                    }
//                    $appointed_time = strtotime($booking->preparation_time) + $offer->pre_time * 60;
//                echo "<p> appointed time is <p>".$booking->preparation_time."timestamp is ".strtotime($booking->preparation_time)."<br>";
                    if (
                        !$this->bookingMatch($car['pre_time'], 'car', $car->car_id,
                            $appointed_time, $estimate_duration,
                            $d_lat, $d_lng, $a_lat, $a_lng, $booking_id) ||
                        !$this->checkCarSpecifiedTimeAvailable($car->car_id, $appointed_time, $estimate_duration)
                    ) {
                        if ($car->car_id != $booking_car['car_id']) {
                            $key = $cars->search($car);
                            $cars->pull($key);
                            continue;
                        }
                    }
                    if(!$offerResult['offerError']['car']){
                        $offerResult['offerError']['car'] = true;
                    }
                    $drivers = $this->getOfferDrivers($company_id, $offer, $car->car_id, $appointed_time, $token);
                    foreach ($drivers as $driver) {
                        if ($markDriver == 1 && $driver->driver_id == $booking_driver_id && $check_driver_pass) {
                            $markDriver = 2;
                            continue;
                        }
                        if (
                            !$this->bookingMatch($car['pre_time'], 'driver', $driver->driver_id,
                                $appointed_time, $estimate_duration,
                                $d_lat, $d_lng, $a_lat, $a_lng, $booking_id) ||
                            !$this->checkDriverSpecifiedTimeAvailable($driver->driver_id,
                                $appointed_time, $estimate_duration)
                        ) {
                            if($driver->driver_id != $booking_driver_id){
                                $key = $drivers->search($driver);
                                $drivers->pull($key);
                                continue;
                            }
                        }
                    }
                    if ($markDriver == 1) {
                        $markDriver = 2;
                        $driver = Driver::leftjoin('users', 'drivers.user_id', '=', 'users.id')
                            ->where('drivers.id', $booking_driver_id)
                            ->select('drivers.id as driver_id',
                                'drivers.delay_time',
                                'drivers.license_number',
                                'drivers.hidden_last',
                                'users.first_name',
                                'users.last_name', 'users.avatar_url',
                                'users.gender', 'users.mobile', "users.email",
                                DB::raw(UrlSpell::getUrlSpell()->getSpellAvatarInDB('users.updated_at',
                                        'users.avatar_url', 'drivers.id', $token,
                                        UrlSpell::companyDriverType) . " as avatar_url"))
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
                    if($company_id == $offer->company_id){
                        array_push($offerResult['carModels'],$car->car_model_id);
                    }
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
                    if (!empty($car)) {
                        $cars->push($car);
                    }
                }
                $offer_car_category->cars = $cars->every(1);
                if ($cars->count() == 0) {
                    $carsKey = $offer_car_categories->search($offer_car_category);
                    $offer_car_categories->pull($carsKey);
                    continue;
                }

            }
            if ($offer_car_categories->count() == 0) {
                continue;
            }
            $offer->car_categories = $offer_car_categories->every(1);

            //4.查出booking对应option
            $this->getOfferOptions($offer);


            array_push($offerResult['offer'],$offer);
        }
        return $offerResult;
    }
}