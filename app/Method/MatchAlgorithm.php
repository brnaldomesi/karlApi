<?php
/**
 * Created by PhpStorm.
 * User: liqihai
 * Date: 16/9/2
 * Time: 上午10:34
 */

namespace App\Method;


use App\Model\Booking;
use App\Model\C2CMatch;
use App\Model\Calendar;
use App\Model\CalendarEvent;
use App\Constants;
use App\Model\Car;
use App\Model\CompanyAnSetting;
use App\Model\CompanySetting;
use App\Model\Driver;
use App\Model\LnAskRecord;
use App\Model\LnProvideRecord;
use App\Model\Offer;
use App\Model\OfferDriverCar;
use App\Model\OfferOption;
use App\Model\Option;
use App\Model\Order;
use Illuminate\Support\Facades\DB;

abstract class MatchAlgorithm
{

    const  AN_TYPE_NORMAL = 0;
    const  AN_TYPE_LN = 1;
    const  AN_TYPE_GN = 2;

    /**
     * @param $company_id
     * @param $unit
     * @param $d_lat
     * @param $d_lng
     * @param $a_lat
     * @param $a_lng
     * @param $estimate_distance
     * @param $appointed_time
     * @param null $offer_id
     * @param  $an
     * @param  $combine
     * @param  $excComId array
     * @return mixed
     */
    protected function offerP2PMatch($company_id, $unit,
                                     $d_lat, $d_lng, $a_lat, $a_lng,
                                     $estimate_distance, $appointed_time,
                                     $offer_id = null,
                                     $an = self::AN_TYPE_NORMAL,
                                     $combine = CompanyAnSetting::COMBINE_DISABLE,
                                     $excComId = null)
    {
        return $this->OfferMatch($company_id, Booking::CHECK_TYPE_DISTANCE,
            $d_lat, $d_lng, $a_lat, $a_lng,
            $estimate_distance,
            0,
            $appointed_time,
            $offer_id, $an, $combine, $excComId, $unit);
    }

    protected function offerHourlyMatch($company_id, $unit,
                                        $d_lat, $d_lng,
                                        $estimate_duration,
                                        $appointed_time,
                                        $offer_id = null,
                                        $an = self::AN_TYPE_NORMAL,
                                        $combine = CompanyAnSetting::COMBINE_DISABLE,
                                        $excComId = null)
    {
        return $this->OfferMatch($company_id, Booking::CHECK_TYPE_HOURLY,
            $d_lat, $d_lng, null, null,
            null,
            $estimate_duration,
            $appointed_time,
            $offer_id, $an, $combine, $excComId, $unit);
    }


    protected function offerLnMatch($company_id, $unit, $d_lat, $d_lng)
    {
        return count($this->OfferMatch($company_id, null,
            $d_lat, $d_lng, null, null,
            null,
            null,
            null,
            null, self::AN_TYPE_NORMAL, CompanyAnSetting::COMBINE_DISABLE, null, $unit));
    }

    /**
     * 调用   MatchAlgorithm::offerLnMatch
     *       MatchAlgorithm::offerHourlyMatch
     *       MatchAlgorithm::offerP2PMatch
     * @param $company_id
     * @param $type
     * @param $d_lat
     * @param $d_lng
     * @param $a_lat
     * @param $a_lng
     * @param $estimate_distance
     * @param $estimate_duration
     * @param $appointed_time
     * @param null $offer_id
     * @param $an
     * @param $combine
     * @param $excComId
     * @param $unit
     * @return mixed
     */
    private function OfferMatch($company_id, $type,
                                $d_lat, $d_lng, $a_lat, $a_lng,
                                $estimate_distance,
                                $estimate_duration,
                                $appointed_time,
                                $offer_id = null, $an, $combine, $excComId, $unit)
    {
        if (is_null($offer_id)) {
            $selectArray = ['offers.id as offer_id', 'offers.cost_min',
                'o_p.prices',
                'offers.tva', 'offers.calc_method', 'offers.company_id',
                'offers.d_is_port',
                DB::raw("case when offers.d_is_port = " . Offer::IS_AIRPORT . " 
                    THEN offers.d_port_price 
                     ELSE 0
                     end as d_port_price"),
                'offers.a_is_port',
                DB::raw("case when offers.a_is_port = " . Offer::IS_AIRPORT . " 
                    THEN offers.a_port_price 
                     ELSE 0
                     end as a_port_price"),
                'company_settings.distance_unit as unit',
                'companies.ccy',
                'companies.name', 'companies.email',
                'companies.phone1', 'companies.phone2',
                DB::raw("case when offers.company_id={$company_id}
                    then 0 
                    else if(({$combine}=1)&&({$an}=1),0,1) end as combine
                "),
                DB::raw("case when offers.company_id={$company_id}
                    then 0 
                    else 1 end as an_offer
                "),
                DB::raw(UrlSpell::getUrlSpell()->getCompaniesLogoByNameInDB("logo"))];
        } else {
            $selectArray = [
                'offers.id as offer_id',
                'offers.cost_min',
                'offers.type',
                'o_p.prices',
                'offers.tva', 'offers.calc_method',
                'offers.company_id',
                'offers.d_is_port',
                'companies.ccy', 'companies.ccy',
                'company_settings.distance_unit as unit',
                DB::raw("case when offers.d_is_port = " . Offer::IS_AIRPORT . " 
                    THEN offers.d_port_price 
                     ELSE 0
                     end as d_port_price"),
                'offers.a_is_port',
                DB::raw("case when offers.a_is_port = " . Offer::IS_AIRPORT . " 
                    THEN offers.a_port_price 
                     ELSE 0
                     end as a_port_price")
            ];
        }

        $offers = Offer::leftjoin("company_an_settings", "company_an_settings.company_id", "=", "offers.company_id")
            ->leftjoin("company_settings", "company_settings.company_id", "=", "offers.company_id")
            ->leftjoin("companies", "companies.id", "=", "offers.company_id")
            ->leftjoin(DB::raw("(SELECT 
  CONCAT('[',
         group_concat(
             CONCAT('{\"invl_start\":\"',round(invl_start,2),'\"'),
             CONCAT(',\"invl_end\":\"',round(invl_end,2),'\"'),
             CONCAT(',\"price\":\"',round(price,2)),'\"}'),
         ']')AS prices,offer_id,count(*) as price_count
FROM offer_prices 
GROUP BY offer_id) as o_p"), "o_p.offer_id", "=", "offers.id")
            ->where(function ($query) use ($type) {
                if ($type != null) {
                    $query->where('offers.check_type', $type);
                }
            })
            ->where(function ($query) use ($an, $company_id, $offer_id, $excComId) {
                if ($offer_id == null) {
                    if ($an == self::AN_TYPE_NORMAL) {
                        $query->where('offers.company_id', $company_id);
                    } else if ($an == self::AN_TYPE_LN) {
                        $matchComIds = DB::select("select group_concat(c2c_match.to_com_id) as matchIds from c2c_match where c2c_match.from_com_id = {$company_id} ");
                        $matchComIds = json_decode(json_encode($matchComIds));
                        $query->where('company_an_settings.ln', CompanyAnSetting::LN_ENABLE)
                            ->where("company_an_settings.locked", CompanyAnSetting::AN_UNLOCKED)
                            ->whereIn("offers.company_id", explode(",", $matchComIds[0]->matchIds))
                            ->where('offers.company_id', "!=", $company_id);
//                            ->whereRaw("companies.ccy = (select ccy from companies where companies.id={$company_id})");
                        if (!is_null($excComId)) {
                            $query->where('offers.company_id', '!=', $excComId);
                        }
                    } else if ($an == self::AN_TYPE_GN) {
                        $matchComIds = DB::select("select group_concat(c2c_match.to_com_id) as matchIds from c2c_match where c2c_match.from_com_id = {$company_id} ");
                        $matchComIds = json_decode(json_encode($matchComIds));
                        $query->where('company_an_settings.gn', CompanyAnSetting::GN_ENABLE)
                            ->where("company_an_settings.locked", CompanyAnSetting::AN_UNLOCKED)
                            ->where('offers.company_id', "!=", $company_id)
                            ->whereNotIn("offers.company_id", explode(",", $matchComIds[0]->matchIds));
//                            ->whereRaw("companies.ccy = (select ccy from companies where companies.id={$company_id})");
                        if (!is_null($excComId)) {
                            $query->where('offers.company_id', '!=', $excComId);
                        }
                    }
                }
            })
            ->where(function ($query) use ($type, $estimate_distance, $estimate_duration, $unit) {
                if ($type == Booking::CHECK_TYPE_DISTANCE) {
                    if ($unit == CompanySetting::UNIT_MI) {
                        $query->where('offers.mi_min', '<=', $estimate_distance)
                            ->where('offers.mi_max', '>=', $estimate_distance);
                    } else {
                        $query->where('offers.km_min', '<=', $estimate_distance)
                            ->where('offers.km_max', '>=', $estimate_distance);
                    }
                } else if ($type == Offer::CHECK_TYPE_HOURLY) {
                    $query->where('offers.duration_min', '<=', $estimate_duration)
                        ->where('offers.duration_max', '>=', $estimate_duration);
                }
            })
            ->where(function ($query) use ($d_lat, $d_lng, $unit) {
                if ($unit == CompanySetting::UNIT_MI) {
                    $query->whereRaw("offers.d_radius > (" . Constants::MI_EARTH_R . " * acos(cos(radians(" . $d_lat . ")) * cos(radians(offers.d_lat)) * cos(radians
                               (offers.d_lng) - radians(" . $d_lng . ")) + sin(radians(" . $d_lat . ")) * sin(radians(offers.d_lat))))");
                } else {
                    $query->whereRaw("offers.d_radius > (" . Constants::KM_EARTH_R . " * acos(cos(radians(" . $d_lat . ")) * cos(radians(offers.d_lat)) * cos(radians
                               (offers.d_lng) - radians(" . $d_lng . ")) + sin(radians(" . $d_lat . ")) * sin(radians(offers.d_lat))))");
                }
            })
            ->where(function ($query) use ($type, $a_lng, $a_lat, $unit) {
                if ($type == Booking::CHECK_TYPE_DISTANCE) {
                    if ($unit == CompanySetting::UNIT_MI) {
                        $query->whereRaw("offers.a_radius > (" . Constants::MI_EARTH_R . " * acos(cos(radians(" . $a_lat . ")) * cos(radians(offers.a_lat)) * cos(radians
                            (offers.a_lng) - radians(" . $a_lng . ")) + sin(radians(" . $a_lat . ")) * sin(radians(offers.a_lat))))")
                            ->orWhere('offers.type', Offer::SHOW_TYPE_TRAN);
                    } else {
                        $query->whereRaw("offers.a_radius > (" . Constants::KM_EARTH_R . " * acos(cos(radians(" . $a_lat . ")) * cos(radians(offers.a_lat)) * cos(radians
                            (offers.a_lng) - radians(" . $a_lng . ")) + sin(radians(" . $a_lat . ")) * sin(radians(offers.a_lat))))")
                            ->orWhere('offers.type', Offer::SHOW_TYPE_TRAN);
                    }

                }
            })
            ->where(function ($query) use ($offer_id) {
                if ($offer_id != null) {
                    $query->where('offers.id', $offer_id);
                }
            })
            ->select($selectArray)
            ->orderBy("companies.id", "asc")
            ->orderBy("offers.id", "asc")
            ->get();
        return $offers;
    }

    protected function bookingMatch($pre_time, $type, $search_id, $appointed_time, $estimate_duration,
                                    $d_lat, $d_lng, $a_lat, $a_lng, $booking_id = null)
    {

        /**
         *  需要判断车和司机的pre_time 和daley_time
         *  1.先匹配车上一个订单和下一个订单，
         *  2.再匹配司机上一个订单和下一个订单
         */

        $searchType = $type == 'driver' ? 'driver_id' : 'car_id';
        //1.1获取上一个和下一个订单

        $orderState = [
            Order::ORDER_STATE_ADMIN_CANCEL,
            Order::ORDER_STATE_SUPER_ADMIN_CANCEL,
            Order::ORDER_STATE_PASSENGER_CANCEL,
            Order::ORDER_STATE_TIMES_UP_CANCEL,
            Order::ORDER_STATE_WAIT_DETERMINE,
        ];


        $bookBefore = Booking::leftJoin('orders', 'orders.booking_id', '=', 'bookings.id')
            ->whereRaw("unix_timestamp(bookings.appointed_at)<={$appointed_time}")
            ->where("bookings." . $searchType, $search_id)
            ->whereNotIn('orders.order_state', $orderState)
            ->where(function ($query) use ($booking_id) {
                if ($booking_id != null) {
                    $query->where('bookings.id', '!=', $booking_id);
                }
            })
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
            ->orderBy('bookings.appointed_at', 'desc')
            ->first();
        $bookAfter = Booking::leftJoin('orders', 'orders.booking_id', '=', 'bookings.id')
            ->whereRaw("unix_timestamp(bookings.appointed_at)>={$appointed_time}")
            ->where("bookings." . $searchType, $search_id)
            ->whereNotIn('orders.order_state', $orderState)
            ->where(function ($query) use ($booking_id) {
                if ($booking_id != null) {
                    $query->where('bookings.id', '!=', $booking_id);
                }
            })
            ->select(
                DB::raw("unix_timestamp(bookings.appointed_at) AS appointed_at"),
                "bookings.id",
                "bookings.d_lat",
                "bookings.d_lng",
                "bookings.car_data"
            )
            ->orderBy('bookings.appointed_at', 'asc')
            ->first();

        //1.2算出上一个订单到当前订单的时间
        if (!empty($bookBefore)) {

            $pre_time = $pre_time * Constants::MINUTE;
            $beforeTime = $this->distanceAlgorithm($bookBefore->a_lng, $bookBefore->a_lat, $d_lng, $d_lat);
            if ($bookBefore->end_time + $pre_time + $beforeTime > $appointed_time) {
                return false;
            }
        }
        //1.3算出当前订单到下一个订单
        if (!empty($bookAfter)) {
            $pre_time = json_decode($bookAfter->car_data)->pre_time;
            $afterTime = $this->distanceAlgorithm($a_lng, $a_lat, $bookAfter->d_lng, $bookAfter->d_lat);
            if ($appointed_time + ($estimate_duration + $pre_time) * Constants::MINUTE + $afterTime > $bookAfter->appointed_at) {
                return false;
            }
        }
        return true;
    }

    /**
     * 根据两点间的经纬度算出算出行程时间。
     * @param $start_lng
     * @param $start_lat
     * @param $end_lng
     * @param $end_lat
     * @return float
     */
    protected function distanceAlgorithm($start_lng, $start_lat, $end_lng, $end_lat)
    {
        $distance = Constants::EARTH_R * acos(cos(deg2rad($start_lat)) * cos(deg2rad($end_lat)) * cos(deg2rad
                    ($end_lng) - deg2rad($start_lng)) + sin(deg2rad($start_lat)) * sin(deg2rad($end_lat)));
        if (is_nan($distance)) {
            $distance = 10;
        }
        return floatval($distance / Constants::MIN_SPEED * Constants::HOUR_SECONDS);
    }

    protected function getOfferCars($company_id, $token, $offer, $appointed_at, $car_category_id = 0, $carModels = null)
    {
        $carCategories = OfferDriverCar::leftjoin('cars', 'cars.id', '=', 'offer_driver_cars.car_id')
            ->leftjoin('car_models', 'car_models.id', '=', 'cars.car_model_id')
            ->leftjoin('car_categories', 'car_categories.id', '=', 'car_models.car_category_id')
            ->where('offer_driver_cars.offer_id', $offer->offer_id)
            ->where(function ($query) use ($carModels) {
                if (!is_null($carModels) || count($carModels) > 0) {
                    $query->whereNotIn('car_models.id', array_unique($carModels));
                }
            })
            ->where(function ($query) use ($car_category_id) {
                if ($car_category_id != 0) {
                    $query->where('car_categories.id', $car_category_id);
                }
            })
            ->select('car_categories.id as category_id',
                'car_categories.name as category')
            ->groupBy('car_categories.id')
            ->get();
        foreach ($carCategories as $carCategory) {
            $cars = OfferDriverCar::leftjoin('cars', 'cars.id', '=', 'offer_driver_cars.car_id')
                ->where('offer_driver_cars.offer_id', $offer->offer_id)
                ->leftjoin('car_models', 'car_models.id', '=', 'cars.car_model_id')
                ->leftjoin('car_brands', 'car_brands.id', '=', 'car_models.car_brand_id')
                ->leftjoin('car_categories', 'car_categories.id', '=', 'car_models.car_category_id')
                ->where('car_models.car_category_id', $carCategory->category_id)
                ->whereRaw("cars.pre_time*60 < ({$appointed_at}-unix_timestamp(now()))")
                ->where(function ($query) use ($company_id, $offer, $carModels) {
                    if ($company_id != $offer->company_id) {
                        $match = C2cMatch::where("from_com_id", $company_id)
                            ->where("to_com_id", $offer->company_id)
                            ->first();
                        if (!empty($match)) {
                            $ownSetting = CompanyAnSetting::where('company_id', $company_id)->first();
                            $exeSetting = CompanyAnSetting::where('company_id', $offer->company_id)->first();
                            if (
                                $ownSetting->ln == CompanyAnSetting::LN_ENABLE &&
                                $exeSetting->ln == CompanyAnSetting::LN_ENABLE
                            ) {
                                $askInSecret = LnAskRecord::where("company_id", $company_id)
                                    ->select(DB::raw('group_concat(car_model_id) as models'))
                                    ->where("secret", LnAskRecord::SECRET_ASK)
                                    ->first();
                                $askInNeed = LnAskRecord::where("company_id", $company_id)
                                    ->select(DB::raw('group_concat(car_model_id) as models'))
                                    ->where("needed", LnAskRecord::NEEDED)
                                    ->first();

                                $provideInSecret = LnProvideRecord::where("company_id", $offer->company_id)
                                    ->select(DB::raw('group_concat(car_id) as cars'))
                                    ->where("secret", LnProvideRecord::SECRET_ASK)
                                    ->first();

                                $provideInProvide = LnProvideRecord::where("company_id", $offer->company_id)
                                    ->select(DB::raw('group_concat(car_id) as cars'))
                                    ->where("provide", LnProvideRecord::PROVIDED)
                                    ->first();

                                $query->where(function ($query) use ($askInSecret, $provideInSecret) {
                                    if (!is_null($askInSecret->models) && !is_null($provideInSecret->cars)) {
                                        $query->whereIn("cars.car_model_id", explode(',', $askInSecret->models))
                                            ->whereIn("cars.id", explode(",", $provideInSecret->cars));
                                    }
                                })->orWhere(function ($query) use ($askInNeed, $provideInProvide) {
                                    if (!is_null($askInNeed->models) && !is_null($provideInProvide->cars)) {
                                        $query->whereIn("cars.car_model_id", explode(',', $askInNeed->models))
                                            ->whereIn("cars.id", explode(",", $provideInProvide->cars));
                                    }

                                });

//                                    ->whereRaw(Car::SEARCH_BD_8);
                                if (!is_null($carModels) || count($carModels) > 0) {
                                    $query->whereNotIn('car_models.id', array_unique($carModels));
                                }
                            }
                        }
                    } else {
                        $query->whereRaw(Car::SEARCH_BD_8);
                    }
                })
                ->select('offer_driver_cars.car_id', 'car_models.name as model',
                    'cars.bags_max',
                    'cars.seats_max',
                    'cars.pre_time',
                    'cars.license_plate as license_plate',
                    'cars.year as year',
                    'cars.color as color',
                    'cars.company_id',
                    'car_brands.name as brand',
                    'car_models.id as car_model_id',
                    'car_brands.id as car_brand_id',
                    'car_categories.id as car_category_id',
                    DB::raw("cars.company_id!={$company_id} as in_an"),
                    UrlSpell::getUrlSpell()->getCarsImgInDB($offer->company_id, $token))
                ->groupBy('cars.id')
                ->inRandomOrder()
                ->get();
            $carCategory->cars = $cars;
        }
        return $carCategories;
    }


    protected function getOfferDrivers($company_id, $offer, $car_id, $appointed_at, $token)
    {
        $drivers = OfferDriverCar::leftjoin('drivers', 'offer_driver_cars.driver_id', '=', 'drivers.id')
            ->leftjoin('users', 'drivers.user_id', '=', 'users.id')
            ->where('offer_driver_cars.offer_id', $offer->offer_id)
            ->where('offer_driver_cars.car_id', $car_id)
            ->where(function ($query) use ($company_id, $offer) {
                if ($company_id != $offer->company_id) {
                    $query->whereRaw(Driver::SEARCH_BD_8);
                }
            })
            ->whereRaw("drivers.delay_time*60 < {$appointed_at}-unix_timestamp(now())")
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

    public function checkOfferSpecifiedTimeAvailable($offer_id, $appointed_time, $duration)
    {
        $dst = MethodAlgorithm::checkDstForOffer($offer_id, $appointed_time);
        $offerCalendar = Calendar::where('owner_id', $offer_id)->where('type', Calendar::OFFER_TYPE)
            ->select('id',
                DB::raw("case when " . $dst . " then dst_routine else routine end as routine"))
            ->first();
        if (empty($offerCalendar)) {
            return false;
        }
        return $this->matchRoutineAndAppointTime($offerCalendar, $appointed_time, $duration);
    }

    protected function getOfferOptions($offer)
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
                } else {
                }
            }
        } else {
        }
        $offer->options = $options;
    }

    protected function checkCarSpecifiedTimeAvailable($car_id, $appointed_time, $duration)
    {
        $dst = MethodAlgorithm::checkDstForCar($car_id, $appointed_time);

        $Calendar = Calendar::where('owner_id', $car_id)
            ->where('type', Calendar::CAR_TYPE)
            ->select('id', DB::raw("case when " . $dst . " then dst_routine else routine end as routine"))
            ->first();

        if (empty($Calendar)) {
            return false;
        }
        return $this->matchRoutineAndAppointTime($Calendar, $appointed_time, $duration);
    }

    protected function checkDriverSpecifiedTimeAvailable($driver_id, $appointed_time, $duration)
    {
        $dst = MethodAlgorithm::checkDstForDriver($driver_id, $appointed_time);

        $offerCalendar = Calendar::where('owner_id', $driver_id)
            ->where('type', Calendar::DRIVER_TYPE)
            ->select(
                'id',
                DB::raw("case when " . $dst . " then dst_routine else routine end as routine"))
            ->first();

        if (empty($offerCalendar)) {
            return false;
        }
        return $this->matchRoutineAndAppointTime($offerCalendar, $appointed_time, $duration);
    }

    protected function matchRoutineAndAppointTime($calendar, $appointed_time, $duration, $inBooking = false)
    {
//        echo 'calendar id '.$calendar->id.'<br>';
        $routine = json_decode($calendar->routine, true);
        $startTime = $this->startTime($appointed_time);
        $endTime = $this->endTime($appointed_time, $duration);


//        echo "start of week ".($startTime)." end of week ".($endTime)."<br>";
//        echo "start of week ".date('Y-m-d H:i:s',$startTime)." end of week ".date('Y-m-d H:i:s',$endTime)."<br>";
        //增加五分钟时长
        if ($startTime < time() + 300) {
            return false;
        }
        $startDayOfWeek = date('w', $startTime);
        $endDayOfWeek = date('w', $endTime);
//        echo "start of week ".$startDayOfWeek." end of week ".$endDayOfWeek."<br>";


        $startOfDay = ($startTime % Constants::DAY_SECONDS) / Constants::HALF_HOUR;
        $endOfDay = ($endTime % Constants::DAY_SECONDS) / Constants::HALF_HOUR;

//        echo "start of day ".$startOfDay." end of day ".$endOfDay."<br>";
        if ($startDayOfWeek == $endDayOfWeek) {
            $string = substr($routine[$startDayOfWeek], $startOfDay, $endOfDay - $startOfDay);
        } else {
            $startDayString = $routine[$startDayOfWeek];
            $endDayString = $routine[$endDayOfWeek];

            $startString = substr($startDayString, $startOfDay, Constants::DAY_SECONDS - $startOfDay);
            $endString = substr($endDayString, 0, $endOfDay);

            $string = $startString . $endString;
        }
//        //检测字符串中是否包含字符1
//        echo $string."<br>";
        if (!empty(strstr($string, '1'))) {
            return false;
        }

        $events = $this->matchEvents($calendar, $startTime, $endTime, $inBooking);
//        echo $events."<br>";
//        echo   "count ".json_encode(count($events) == 0)."<br>";
        return count($events) == 0;
    }


    protected function startTime($appointed_at)
    {
        return $appointed_at - ($appointed_at) % Constants::HALF_HOUR;
    }

    protected function endTime($appointed_at, $duration)
    {
        return ceil(($appointed_at + $duration * Constants::MINUTE) / Constants::HALF_HOUR) * Constants::HALF_HOUR;
    }


    protected function matchEvents($calendar, $startTime, $endTime, $inBooking)
    {
        $events = CalendarEvent::where([['calendar_id', $calendar->id], ['enable', CalendarEvent::EVENT_ENABLE]])
            ->where(function ($query) use ($startTime, $endTime) {
                $query
                    ->where(function ($childQuery) use ($startTime, $endTime) {
                        $childQuery
                            ->where(DB::raw('unix_timestamp(start_time)'), '>=', $startTime)//      |---------------|
                            ->where(DB::raw('unix_timestamp(start_time)'), '<', $endTime)//  |------------|
                            ->where(DB::raw('unix_timestamp(end_time)'), '>', $endTime);
                    })
                    ->orWhere(function ($childQuery) use ($startTime, $endTime) {
                        $childQuery
                            ->where(DB::raw('unix_timestamp(start_time)'), '>=', $startTime)//       |---------|
                            ->where(DB::raw('unix_timestamp(end_time)'), '<=', $endTime);        //    |---------------|
                    })
                    ->orWhere(function ($childQuery) use ($startTime, $endTime) {
                        $childQuery
                            ->where(DB::raw('unix_timestamp(start_time)'), '<=', $startTime)//       |---------|
                            ->where(DB::raw('unix_timestamp(end_time)'), '>=', $endTime);        //         |-----|
                    })
                    ->orWhere(function ($childQuery) use ($startTime, $endTime) {
                        $childQuery
                            ->where(DB::raw('unix_timestamp(start_time)'), '<', $startTime)//      |---------------|
                            ->where(DB::raw('unix_timestamp(end_time)'), '>', $startTime)//               |------------|
                            ->where(DB::raw('unix_timestamp(end_time)'), '<', $endTime);
                    });
            })
            ->where(function ($query) use ($inBooking, $calendar) {
                if ($inBooking) {
                    $query->where('creator_id', '!=', $calendar->booking_id)
                        ->where('creator_type', CalendarEvent::CREATOR_TYPE_BOOKING);
                }
            })
            ->get();
        return $events;
    }


    protected function getDriver($driver_id, $offer_id, $car_id, $token)
    {
        $drivers = OfferDriverCar::leftjoin('drivers', 'offer_driver_cars.driver_id', '=', 'drivers.id')
            ->leftjoin('users', 'drivers.user_id', '=', 'users.id')
            ->where('offer_driver_cars.driver_id', $driver_id)
            ->where('offer_driver_cars.offer_id', $offer_id)
            ->where('offer_driver_cars.car_id', $car_id)
            ->select('offer_driver_cars.driver_id',
                'drivers.license_number',
                'drivers.hidden_last',
                'users.first_name',
                'users.last_name', 'users.avatar_url', "users.lang",
                'users.gender', 'users.mobile', "users.email",
                DB::raw(UrlSpell::getUrlSpell()->getSpellAvatarInDB('users.updated_at', 'users.avatar_url', 'offer_driver_cars.driver_id', $token, UrlSpell::companyDriverType) . " as avatar_url"))
            ->first();
        return $drivers;
    }


    protected function getCars($car_id, $offer_id, $company_id, $token)
    {
        $car = OfferDriverCar::leftjoin('cars', 'cars.id', '=', 'offer_driver_cars.car_id')
            ->where('offer_driver_cars.car_id', $car_id)
            ->where('offer_driver_cars.offer_id', $offer_id)
            ->leftjoin('car_models', 'car_models.id', '=', 'cars.car_model_id')
            ->leftjoin('car_brands', 'car_brands.id', '=', 'car_models.car_brand_id')
            ->leftjoin('company_settings', "company_settings.company_id", "=", "cars.company_id")
            ->select('offer_driver_cars.car_id', 'car_models.name as model', "cars.pre_time",
                'cars.bags_max', 'cars.seats_max', 'car_models.car_category_id',
                "company_settings.lang",
                'cars.license_plate as license_plate', 'cars.year as year', 'cars.color as color',
                UrlSpell::getUrlSpell()->getCarsImgInDB($company_id, $token),
                'car_brands.name as brand')
            ->first();

        return $car;
    }
}