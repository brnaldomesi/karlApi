<?php

namespace App\Model;

use App\Constants;
use App\ErrorCode;
use App\Method\MethodAlgorithm;
use App\Method\UrlSpell;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Offer extends Model
{
    const UNIT_MI=1;
    const UNIT_KM=2;


    const CHECK_TYPE_DISTANCE = 1;
    const CHECK_TYPE_HOURLY = 2;
    const CHECK_TYPE_CUSTOM = 3;

    const CALC_DISTANCE = 1;
    const CALC_HOURLY = 2;

    const IS_AIRPORT = 1;
    const NOT_AIRPORT = 0;

    const SHOW_TYPE_LONG = 1;
    const SHOW_TYPE_HOUR = 2;
    const SHOW_TYPE_TRAN = 3;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'description', 'company_id', 'type','check_type',
        'd_address', 'd_lat', 'd_lng', 'd_radius', 'a_address',
        'a_lat', 'a_lng', 'a_radius', 'mi_mix', 'mi_max',"km_min","km_max",
        'cost_min', 'price', 'tva', 'calc_method',
        'duration_min', 'duration_max', 'd_is_port', 'd_port_price', 'a_is_port', 'a_port_price',
    ];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = [
        'created_at', 'updated_at'
    ];

    /**
     * 修改offer详情
     * @param $param
     * @param $offer_id
     * @param $company_id
     * @param $token
     * @return mixed
     */
    public static function updateOfferInfo($param, $offer_id, $company_id, $token)
    {
        $result = DB::transaction(function () use ($param, $offer_id, $company_id, $token) {
            $offer = self::where('offers.id', $offer_id)->where('company_id', $company_id)->first();
            $setting = CompanySetting::where("company_id",$company_id)->select("distance_unit")->first();
            if (empty($offer)) {
                throw new \Exception(ErrorCode::errorNotExist('offer'));
            }
            $data = json_decode($param, true);
            $name = isset($data['name']) ? $data['name'] : null;
            $description = isset($data['description']) ? $data['description'] : null;
            $type = isset($data['type']) ? $data['type'] : null;
            $d_address = isset($data['d_address']) ? $data['d_address'] : null;
            $d_lat = isset($data['d_lat']) ? $data['d_lat'] : null;
            $d_lng = isset($data['d_lng']) ? $data['d_lng'] : null;
            $d_radius = isset($data['d_radius']) ? $data['d_radius'] : null;
            $a_address = isset($data['a_address']) ? $data['a_address'] : null;
            $a_lat = isset($data['a_lat']) ? $data['a_lat'] : null;
            $a_lng = isset($data['a_lng']) ? $data['a_lng'] : null;
            $a_radius = isset($data['a_radius']) ? $data['a_radius'] : null;
            $cost_min = isset($data['cost_min']) ? $data['cost_min'] : null;
            $prices = isset($data['prices']) ? $data['prices'] : null;
            $tva = isset($data['tva']) ? $data['tva'] : null;
//            $calc_method = isset($data['calc_method']) ? $data['calc_method'] : null;
            $cars = isset($data['cars']) ? $data['cars'] : null;
            $options = isset($data['options']) ? $data['options'] : null;
            $calendar = isset($data['calendar']) ? $data['calendar'] : null;
            $d_is_port = isset($data['d_is_port']) ? $data['d_is_port'] : null;
            $d_port_price = isset($data['d_port_price']) ? $data['d_port_price'] : null;
            $a_is_port = isset($data['a_is_port']) ? $data['a_is_port'] : null;
            $a_port_price = isset($data['a_port_price']) ? $data['a_port_price'] : null;

            if (is_null($name) && is_null($type) &&
                is_null($d_address) && is_null($description) &&
                is_null($d_lat) && is_null($d_lng) &&
                is_null($d_radius) && is_null($a_address) &&
                is_null($a_lat) && is_null($a_lng) &&
                is_null($a_radius) &&
                is_null($cost_min) &&
                is_null($tva) && //is_null($calc_method) &&
                is_null($calendar) &&
                is_null($d_is_port) &&
                is_null($d_port_price) &&
                is_null($a_is_port) &&
                is_null($a_port_price)
            ) {
                throw new \Exception(ErrorCode::errorMissingParam());
            }

            if (!is_null($name)) {
                if (empty($name)) {
                    throw new \Exception(ErrorCode::errorParam('name is empty'));
                } else {
                    $offer->name = $name;
                }
            }

            if (!is_null($type)) {
                if (empty($type) ||
                    ($type != self::SHOW_TYPE_LONG &&
                        $type != self::SHOW_TYPE_HOUR &&
                        $type != self::SHOW_TYPE_TRAN)
                ) {
                    throw new \Exception(ErrorCode::errorParam('type'));
                } else {
                    $offer->type = $type;
                    if ($type == self::SHOW_TYPE_LONG || $type == self::SHOW_TYPE_TRAN) {
                        $offer->calc_method = self::CALC_DISTANCE;
                        $offer->check_type = self::CHECK_TYPE_DISTANCE;
                    } else {
                        $offer->calc_method = self::CALC_HOURLY;
                        $offer->check_type = self::CHECK_TYPE_HOURLY;
                    }
                }
            }

            if (!is_null($d_address)) {
                if (empty($d_address)) {
                    throw new \Exception(ErrorCode::errorParam('address is error type'));
                } else {
                    $offer->d_address = $d_address;
                }
            }

            if (!is_null($d_is_port)) {
                if (!is_numeric($d_is_port) || ($d_is_port != self::NOT_AIRPORT && $d_is_port != self::IS_AIRPORT)) {
                    throw new \Exception(ErrorCode::errorParam('d_is_port'));
                } else {
                    $offer->d_is_port = $d_is_port;
                    switch ($d_is_port) {
                        case self::NOT_AIRPORT :
                            $offer->d_port_price = 0;
                            break;
                        case self::IS_AIRPORT :
                            if (!is_numeric($d_port_price) || $d_port_price < 0) {
                                throw new \Exception(ErrorCode::errorParam('d_port_price'));
                            } else {
                                $offer->d_port_price = $d_port_price;
                            }
                            break;
                    }
                }
            }

            if (!is_null($d_lat)) {
                if (empty($d_lat) || !is_numeric($d_lat) || $d_lat < -90 || $d_lat > 90) {
                    throw new \Exception(ErrorCode::errorParam('d_lat is error type'));
                } else {
                    $offer->d_lat = $d_lat;
                }
            }

            if (!is_null($d_lng)) {
                if (empty($d_lng) || !is_numeric($d_lng) || $d_lng < -180 || $d_lng > 180) {
                    throw new \Exception(ErrorCode::errorParam('d_lng is error type'));
                } else {
                    $offer->d_lng = $d_lng;
                }
            }

            if (!is_null($d_radius)) {
                if (empty($d_radius) || !is_numeric($d_radius) || $d_radius < 0) {
                    throw new \Exception(ErrorCode::errorParam('d_radius is error type'));
                } else {
                    $offer->d_radius = $d_radius;
                }
            }

            if (!is_null($a_address)) {
                if ($offer->type == 1) {
                    if (empty($a_address)) {
                        throw new \Exception(ErrorCode::errorParam('a_address is error type'));
                    } else {
                        $offer->a_address = $a_address;
                    }
                } elseif ($offer->type == 2) {
                    $offer->a_address = "";
                }
            }

            if (!is_null($a_is_port)) {
                if (!is_numeric($a_is_port) || ($a_is_port != self::NOT_AIRPORT && $a_is_port != self::IS_AIRPORT)) {
                    throw new \Exception(ErrorCode::errorParam('a_is_port'));
                } else {
                    $offer->a_is_port = $a_is_port;
                    switch ($a_is_port) {
                        case self::NOT_AIRPORT :
                            $offer->a_port_price = 0;
                            break;
                        case self::IS_AIRPORT :
                            if (!is_numeric($a_port_price) || $a_port_price < 0) {
                                throw new \Exception(ErrorCode::errorParam('a_port_price'));
                            } else {
                                $offer->a_port_price = $a_port_price;
                            }
                            break;
                    }
                }
            }

            if (!is_null($a_lat)) {
                if ($offer->type == 1) {
                    if (empty($a_lat) || !is_numeric($a_lat) || $a_lat < -90 || $a_lat > 90) {
                        throw new \Exception(ErrorCode::errorParam('a_lat is error type'));
                    } else {
                        $offer->a_lat = $a_lat;
                    }
                } elseif ($offer->type == 2) {
                    $offer->a_lat = "";
                }
            }

            if (!is_null($a_lng)) {
                if ($offer->type == 1) {
                    if (empty($a_lng) || !is_numeric($a_lng) || $a_lng < -180 || $a_lng > 180) {
                        throw new \Exception(ErrorCode::errorParam('a_lng is error type'));
                    } else {
                        $offer->a_lng = $a_lng;
                    }
                } elseif ($offer->type == 2) {
                    $offer->a_lng = '';
                }

            }
            if (!is_null($a_radius)) {
                if ($offer->type == 1) {
                    if (empty($a_radius) || !is_numeric($a_radius) || $a_radius < 0) {
                        throw new \Exception(ErrorCode::errorParam('a_radius is error type'));
                    } else {
                        $offer->a_radius = $a_radius;
                    }
                } elseif ($offer->type == 2) {
                    $offer->a_radius = '';
                }
            }


            if (!is_null($cost_min)) {
                if (!is_numeric($cost_min) || $cost_min < 0) {
                    throw new \Exception(ErrorCode::errorParam('cost_min is error type'));
                } else {
                    $offer->cost_min = $cost_min;
                }
            }


            if (!is_null($tva)) {
                if (!is_numeric($tva) || $tva < 0) {
                    throw new \Exception(ErrorCode::errorParam('tva is error type'));
                } else {
                    $offer->tva = $tva;
                }
            }

            if (!is_null($description)) {
                $offer->description = $description;
            }

            $offer->save();


            if (!is_null($options)) {
                if (!empty($options)) {
                    OfferOption::where('offer_options.offer_id', $offer_id)->delete();
                    $option_ids = explode(",", $options);
                    foreach ($option_ids as $option_id) {
                        $option =
                            Option::where('options.company_id', [$company_id])
                                ->orwhere('options.company_id', 0)
                                ->where('options.id', $option_id)
                                ->first();

                        if (empty($option) || $option->parent_id != 0) {
                            throw new \Exception(ErrorCode::errorNotExist('option'));
                        }
                        OfferOption::create(array('offer_id' => $offer->id, 'option_id' => $option_id));
                    }
                } else {
                    OfferOption::where('offer_options.offer_id', $offer_id)->delete();
                }
            }
            if (!is_null($cars)) {
                if (is_array($cars)) {
                    OfferDriverCar::where([['offer_driver_cars.offer_id', $offer_id]])->delete();
                    foreach ($cars as $carInfo) {
                        $car = Car::where('cars.id', $carInfo['car_id'])
                            ->where('cars.company_id', $company_id)
                            ->first();
                        if (empty($car)) {
                            throw new \Exception(ErrorCode::errorNotExist('car'));
                        }
                        $drivers = $carInfo['drivers'];
                        if (!empty($drivers)) {
                            $driver_ids = explode(",", $drivers);
                            foreach ($driver_ids as $driver_id) {
                                $driver = Driver::where('drivers.id', $driver_id)
                                    ->leftjoin('users', 'users.id', '=', 'drivers.user_id')
                                    ->where('users.company_id', $company_id)
                                    ->first();
                                if (empty($driver)) {
                                    throw new \Exception(ErrorCode::errorNotExist('driver'));
                                }
                                OfferDriverCar::create(array(
                                    'offer_id' => $offer->id,
                                    'car_id' => $car->id,
                                    'driver_id' => $driver_id,
                                ));
                            }
                        }
                    }
                } else {
                    throw new \Exception(ErrorCode::errorParam('cars'));
                }
            }

            if (!is_null($prices)) {
                $prices = json_decode($prices, true);
                if (empty($prices)) {
                    throw new \Exception(ErrorCode::errorParam('prices'));
                } else {
                    $prices = MethodAlgorithm::sortPrices($prices);
                    $min = array_first($prices)['invl_start'];
                    $max = array_last($prices)['invl_end'];
                    if ($offer->type == self::SHOW_TYPE_LONG ||
                        $offer->type == self::SHOW_TYPE_TRAN
                    ) {
                        if($setting->distance_unit == CompanySetting::UNIT_MI){
                            $offer->mi_max = $max;
                            $offer->mi_min = $min;

                            $offer->km_max = $max*Constants::MI_2_KM;
                            $offer->km_min = $min*Constants::MI_2_KM;
                        }else{
                            $offer->km_max = $max;
                            $offer->km_min = $min;

                            $offer->mi_max = $max*Constants::KM_2_MI;
                            $offer->mi_min = $min*Constants::KM_2_MI;
                        }
                    } else {
                        $offer->duration_max = $max;
                        $offer->duration_min = $min;
                    }
                    $offer->save();
                    OfferPrice::where('offer_id', $offer_id)->delete();
                    foreach ($prices as $price) {
                        $price['company_id'] = $company_id;
                        $price['offer_id'] = $offer_id;
                        $price['calc_method'] = $offer->calc_method;
                        OfferPrice::create($price);
                    }
                }
            }

            if (!is_null($calendar)) {
                if (empty($calendar)) {
                    throw new \Exception(ErrorCode::errorParam('calendar'));
                } else {
                    if (is_array($calendar)) {
                        if (sizeof($calendar) != 7) {
                            throw new \Exception(ErrorCode::errorParam('calender error'));
                        } else {
                            foreach ($calendar as $cal) {
                                if (!preg_match("/[0,1]{48}/", $cal)) {
                                    throw new \Exception(ErrorCode::errorParam('calender error'));
                                }
                            }

                            $offerCalendar = Calendar::where("type", Calendar::OFFER_TYPE)->where('owner_id', $offer_id)->first();
                            if (empty($offerCalendar)) {
                                throw new \Exception(ErrorCode::errorDB());
                            } else {
                                // 判断是否夏时令
                                $dst = MethodAlgorithm::checkDstForOffer($offer_id);

                                if ($dst) {
                                    $offerCalendar->dst_routine = json_encode($calendar);
                                    $offerCalendar->routine = MethodAlgorithm::shiftString($calendar, true);
                                } else {
                                    $offerCalendar->routine = json_encode($calendar);
                                    $offerCalendar->dst_routine = MethodAlgorithm::shiftString($calendar);
                                }
                                $offerCalendar->save();
                            }
                        }
                    } else {
                        throw new \Exception(ErrorCode::errorParam('calendar'));
                    }
                }
            }
            $offer = self::getOfferDetail($offer_id,$company_id, $token);
            return $offer;
        }
        );
        return $result;
    }

    //获取详情
    public static function getOfferDetail($offerId,$companyId, $token)
    {
        /**
         * SELECT d.id,d.name FROM offer_driver_cars a
         * LEFT JOIN cars c ON c.id = a.car_id LEFT JOIN car_models b on c.car_model_id = b.id
         * LEFT JOIN car_categories d ON d.id = b.car_category_id
         * where c.company_id =1 GROUP BY d.id;
         */


        $offer = Offer::where('offers.id', $offerId)
            ->where(function ($query) use($companyId){
                if(!is_null($companyId)){
                    $query->where('offers.company_id', $companyId);
                }
            })
            ->leftjoin('companies', 'companies.id', '=', 'offers.company_id')
            ->leftjoin('company_settings', 'companies.id', '=', 'company_settings.company_id')
            ->select('offers.*', 'companies.name as company_name',"company_settings.distance_unit")
            ->first();
        if (empty($offer)) {
            return null;
        }

        $carCategories = CarCategory::leftjoin('car_models', 'car_models.car_category_id', '=', 'car_categories.id')
            ->leftjoin('cars', 'cars.car_model_id', '=', 'car_models.id')
            ->select('car_categories.name as category',
                'car_categories.id as category_id')
            ->where('cars.company_id', $offer->company_id)
            ->groupBy('category_id')
            ->get();
        foreach ($carCategories as $carCategory) {
            $cars = Car::leftJoin(DB::raw("(select car_id,offer_id FROM offer_driver_cars WHERE offer_id='" . $offer->id . "' group by car_id) as b"), 'b.car_id', '=', 'cars.id')
                ->leftJoin('car_models', 'cars.car_model_id', '=', 'car_models.id')
                ->leftJoin('car_brands', 'car_models.car_brand_id', '=', 'car_brands.id')
                ->leftJoin('car_categories', 'car_models.car_category_id', '=', 'car_categories.id')
                ->where('car_categories.id', $carCategory->category_id)
                ->where('cars.company_id', $offer->company_id)
                ->select(
                    'cars.id AS car_id',
                    'cars.license_plate',
                    'cars.year AS year',
                    'cars.color AS color',
                    'cars.seats_max AS seats_max',
                    'cars.bags_max AS bags_max',
                    'car_models.name AS model',
                    'car_brands.name AS brand',
                    'car_categories.name AS category',
                    DB::raw("CASE WHEN b.offer_id = '" . $offer->id . "'
                                THEN
                                  1
                                ELSE 0
                             END AS selected"),
                    DB::raw(UrlSpell::getUrlSpell()->getCarsImgInSql($offer->company_id, $token))
                )
                ->get();
            foreach ($cars as $car) {
                $drivers = DriverCar::leftjoin(
                    DB::raw("(select driver_id,car_id,offer_id FROM 
                    offer_driver_cars WHERE offer_id = '" . $offer->id . "' ) as b"), function ($join) {
                    $join->on('b.car_id', '=', 'driver_cars.car_id')
                        ->on('b.driver_id', '=', 'driver_cars.driver_id');
                })
                    ->leftJoin('drivers', 'driver_cars.driver_id', '=', 'drivers.id')
                    ->leftJoin('users', 'users.id', '=', 'drivers.user_id')
                    ->where("driver_cars.car_id", $car->car_id)
                    ->where("users.company_id", $offer->company_id)
                    ->select(
                        DB::raw("
                          CASE WHEN b.offer_id = '" . $offer->id . "' THEN 1 ELSE 0 END AS selected  
                        "),
                        "driver_cars.car_id",
                        "driver_cars.driver_id",
                        'users.address', 'users.lat', 'users.lng',
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

        $offer->car_categories = $carCategories;
        $options = OfferOption::where('offer_options.offer_id', $offer->id)
            ->leftjoin('options', 'options.id', '=', 'offer_options.option_id')
            ->select(
                'offer_options.offer_id', 'options.id as option_id', 'options.title',
                'options.price', 'options.add_max', 'options.description', 'options.type'
            )
            ->get();
//        $options = Option::leftjoin(
//            DB::raw("(select * from offer_options where offer_id={$offer->id}) as b"),
//            'options.id', '=', 'b.option_id')
//            ->select('options.id as option_id', 'options.add_max',
//                'options.price', 'options.title as name',
//                'options.type', 'options.parent_id',
//                DB::raw("case when b.id is null then 0 else 1 end as selected"))
//            ->get();
        if (!empty($options)) {
            foreach ($options as $option) {
                if (strtolower($option->type) == strtolower('GROUP')) {
                    $groupOptions = Option::leftjoin(
                        DB::raw("(select * from offer_options where offer_id={$offer->offer_id}) as b"),
                        'options.id', '=', 'b.option_id')
                        ->where('options.parent_id', $option->option_id)->get();
                    $option->group = $groupOptions;
                }
            }
        }

        $offer->options = $options;

        $prices = OfferPrice::where('offer_id', $offer->id)
            ->select(
                "offer_id",
                "id",
                "company_id",
                "calc_method",
                DB::raw("round(invl_start,2) as invl_start"),
                DB::raw("round(invl_end,2) as invl_end"),
                DB::raw("round(price,2) as price")

            )
            ->get();
        $offer->prices = $prices;

        // 判断是否夏时令
        $dst = MethodAlgorithm::checkDstForOffer($offer->id);

        $routine = Calendar::where('owner_id', $offer->id)->where('type', Calendar::OFFER_TYPE)
            ->select(
                'id',
                'type',
                'owner_id',
                DB::raw("case when " . $dst . " then dst_routine else routine end as routine"),
                'dst',
                'timezone',
                'company_id')
            ->first();
        $offer->calendar = $routine;
        return $offer;
    }

    public static function insertOffer($param, $company_id, $token)
    {
        $result = DB::transaction(function () use ($param, $company_id, $token) {
            $setting = CompanySetting::where("company_id",$company_id)->select("distance_unit")->first();
            $data = json_decode($param, true);
            $name = isset($data['name']) ? $data['name'] : null;
            $description = isset($data['description']) ? $data['description'] : null;
            $type = isset($data['type']) ? $data['type'] : null;
            $d_address = isset($data['d_address']) ? $data['d_address'] : null;
            $d_lat = isset($data['d_lat']) ? $data['d_lat'] : null;
            $d_lng = isset($data['d_lng']) ? $data['d_lng'] : null;
            $d_radius = isset($data['d_radius']) ? $data['d_radius'] : null;
            $a_address = isset($data['a_address']) ? $data['a_address'] : null;
            $a_lat = isset($data['a_lat']) ? $data['a_lat'] : null;
            $a_lng = isset($data['a_lng']) ? $data['a_lng'] : null;
            $a_radius = isset($data['a_radius']) ? $data['a_radius'] : null;
            $cost_min = isset($data['cost_min']) ? $data['cost_min'] : null;
            $prices = isset($data['prices']) ? $data['prices'] : null;
            $tva = isset($data['tva']) ? $data['tva'] : null;
//            $calc_method = isset($data['calc_method']) ? $data['calc_method'] : null;
            $cars = isset($data['cars']) ? $data['cars'] : null;
            $options = isset($data['options']) ? $data['options'] : null;
            $calendar = isset($data['calendar']) ? $data['calendar'] : null;
            $d_is_port = isset($data['d_is_port']) ? $data['d_is_port'] : 0;
            $d_port_price = isset($data['d_port_price']) ? $data['d_port_price'] : 0;
            $a_is_port = isset($data['a_is_port']) ? $data['a_is_port'] : 0;
            $a_port_price = isset($data['a_port_price']) ? $data['a_port_price'] : 0;

            if (is_null($name) || is_null($type) ||
                is_null($d_address) || is_null($d_lat) ||
                is_null($d_lng) || is_null($d_radius) ||
                is_null($cost_min) ||
                is_null($tva) ||
                //is_null($calc_method) ||
                is_null($calendar) || is_null($prices)
            ) {
                throw new \Exception(ErrorCode::errorMissingParam());
            }

            if (empty($name)) {
                throw new \Exception(ErrorCode::errorParam('name is empty'));
            }
            if (empty($type) || ($type != Offer::SHOW_TYPE_LONG &&
                    $type != Offer::SHOW_TYPE_HOUR &&
                    $type != Offer::SHOW_TYPE_TRAN )) {
                throw new \Exception(ErrorCode::errorParam('type'));
            } else {
            }
            if (empty($d_address)) {
                throw new \Exception(ErrorCode::errorParam('address is error type'));
            }
            if (empty($d_lat) || !is_numeric($d_lat) || $d_lat < -90 || $d_lat > 90) {
                throw new \Exception(ErrorCode::errorParam('d_lat is error type'));
            }
            if (empty($d_lng) || !is_numeric($d_lng) || $d_lng < -180 || $d_lng > 180) {
                throw new \Exception(ErrorCode::errorParam('d_lng is error type'));
            }
            if (empty($d_radius) || !is_numeric($d_radius) || $d_radius < 0) {
                throw new \Exception(ErrorCode::errorParam('d_radius is error type'));
            }

            if (!is_numeric($d_is_port) || ($d_is_port != self::NOT_AIRPORT && $d_is_port != self::IS_AIRPORT)) {
                throw new \Exception(ErrorCode::errorParam('d_is_port'));
            }
            if (!is_numeric($d_port_price) || $d_port_price < 0) {
                throw new \Exception(ErrorCode::errorParam('d_port_price'));
            }
            if (!is_numeric($a_is_port) || ($a_is_port != self::NOT_AIRPORT && $a_is_port != self::IS_AIRPORT)) {
                throw new \Exception(ErrorCode::errorParam('a_is_port'));
            }
            if (!is_numeric($a_port_price) || $a_port_price < 0) {
                throw new \Exception(ErrorCode::errorParam('a_port_price'));
            }
            $prices = json_decode($prices, true);
            if (empty($prices)) {
                throw new \Exception(ErrorCode::errorParam('price is error type'));
            }

            $prices = MethodAlgorithm::sortPrices($prices);
            $min = array_first($prices)['invl_start'];
            $max = array_last($prices)['invl_end'];
            if ($type == self::SHOW_TYPE_LONG) {
                if (is_null($a_address) || is_null($a_lat) ||
                    is_null($a_lng) || is_null($a_radius)
                ) {
                    throw new \Exception(ErrorCode::errorMissingParam());
                }
                if (empty($a_address)) {
                    throw new \Exception(ErrorCode::errorParam('a_address is error type'));
                }
                if (empty($a_lat) || !is_numeric($a_lat) || $a_lat < -90 || $a_lat > 90) {
                    throw new \Exception(ErrorCode::errorParam('a_lat is error type'));
                }
                if (empty($a_lng) || !is_numeric($a_lng) || $a_lng < -180 || $a_lng > 180) {
                    throw new \Exception(ErrorCode::errorParam('a_lng is error type'));
                }
                if (empty($a_radius) || !is_numeric($a_radius) || $a_radius < 0) {
                    throw new \Exception(ErrorCode::errorParam('a_radius is error type'));
                }
                if($setting->distance_unit == CompanySetting::UNIT_MI){
                    $data['mi_max'] = $max;
                    $data['mi_min'] = $min;
                    $data['km_max'] = $max*Constants::MI_2_KM;
                    $data['km_min'] = $min*Constants::MI_2_KM;
                }else{
                    $data['km_max'] = $max;
                    $data['km_min'] = $min;
                    $data['mi_max'] = $max*Constants::KM_2_MI;
                    $data['mi_min'] = $min*Constants::KM_2_MI;
                }


                $data['check_type'] = self::CHECK_TYPE_DISTANCE;
                $data['distance_max'] = $max;
                $data['distance_min'] = $min;
                $data['calc_method'] = self::CALC_DISTANCE;
            } elseif ($type == Offer::SHOW_TYPE_HOUR) {
                $data['duration_max'] = $max;
                $data['duration_min'] = $min;
                $data['calc_method'] = self::CALC_HOURLY;
                $data['check_type'] = self::CHECK_TYPE_HOURLY;
                $data['a_is_port'] = 0;
                $data['a_port_price'] = 0;
            } elseif ($type == Offer::SHOW_TYPE_TRAN) {
                $data['check_type'] = self::CHECK_TYPE_DISTANCE;
                $data['calc_method'] = self::CALC_DISTANCE;
                if($setting->distance_unit == CompanySetting::UNIT_MI){
                    $data['mi_max'] = $max;
                    $data['mi_min'] = $min;
                    $data['km_max'] = $max*Constants::MI_2_KM;
                    $data['km_min'] = $min*Constants::MI_2_KM;
                }else{
                    $data['km_max'] = $max;
                    $data['km_min'] = $min;
                    $data['mi_max'] = $max*Constants::KM_2_MI;
                    $data['mi_min'] = $min*Constants::KM_2_MI;
                }
            }

            if (!is_numeric($cost_min) || $cost_min < 0) {
                throw new \Exception(ErrorCode::errorParam('costmin is error type'));
            }
            if (!is_numeric($tva) || $tva < 0) {
                throw new \Exception(ErrorCode::errorParam('tva is error type'));
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
            \Log::info(json_encode($data));
            $data['company_id'] = $company_id;
            $offer = self::create($data);
            foreach ($prices as $price) {
                $price['company_id'] = $company_id;
                $price['offer_id'] = $offer->id;
                $price['calc_method'] = $data['calc_method'];
                OfferPrice::create($price);
            }

            if (!empty($options)) {
                $option_ids = explode(",", $options);
                foreach ($option_ids as $option_id) {
                    $option =
                        Option::where('options.company_id', [$company_id])
                            ->orwhere('options.company_id', 0)
                            ->where('options.id', $option_id)
                            ->first();

                    if (empty($option) || $option->parent_id != 0) {
                        throw new \Exception(ErrorCode::errorNotExist('option'));
                    }
                    OfferOption::create(array('offer_id' => $offer->id, 'option_id' => $option_id));
                }
            }
            if (is_array($cars)) {
                foreach ($cars as $carInfo) {
                    $car = Car::where('cars.id', $carInfo['car_id'])
                        ->where('cars.company_id', $company_id)
                        ->first();
                    if (empty($car)) {
                        throw new \Exception(ErrorCode::errorNotExist('car'));
                    }
                    $drivers = $carInfo['drivers'];
                    if (!empty($drivers)) {
                        $driver_ids = explode(",", $drivers);
                        foreach ($driver_ids as $driver_id) {
                            $driver = Driver::where('drivers.id', $driver_id)
                                ->leftjoin('users', 'users.id', '=', 'drivers.user_id')
                                ->where('users.company_id', $company_id)
                                ->first();
                            if (empty($driver)) {
                                throw new \Exception(ErrorCode::errorNotExist('drivers'));
                            }
                            $driverCars = DriverCar::where([['driver_id', $driver_id], ['car_id', $carInfo['car_id']]])->first();
                            if (empty($driverCars)) {
                                throw new \Exception(ErrorCode::errorOfferDriverCar());
                            }
                            OfferDriverCar::create(array(
                                'offer_id' => $offer->id,
                                'car_id' => $car->id,
                                'driver_id' => $driver_id,
                            ));
                        }
                    }
                }
            } else {
                throw new \Exception(ErrorCode::errorParam('cars'));
            }

            $company = Company::select('id', 'dst')->where('id', $company_id)->first();
            $calendar_dst = $company->dst == null ? 0 : $company->dst;

            // 判断是否夏时令
            $dst = MethodAlgorithm::checkDstForOffer($offer->id);

            if ($dst) {
                Calendar::create(['type' => Calendar::OFFER_TYPE,
                    'owner_id' => $offer->id,
                    'routine' => MethodAlgorithm::shiftString($calendar, true),
                    'dst_routine' => json_encode($calendar),
                    'dst' => $calendar_dst,
                    'company_id' => $company_id]);
            } else {
                Calendar::create(['type' => Calendar::OFFER_TYPE,
                    'owner_id' => $offer->id,
                    'routine' => json_encode($calendar),
                    'dst_routine' => MethodAlgorithm::shiftString($calendar),
                    'dst' => $calendar_dst,
                    'company_id' => $company_id]);
            }

            return self::getOfferDetail($offer->id,$company_id, $token);
        }
        );
        return $result;
    }

}
