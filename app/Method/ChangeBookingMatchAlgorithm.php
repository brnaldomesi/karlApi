<?php
/**
 * Created by PhpStorm.
 * User: liqihai
 * Date: 16/9/3
 * Time: 上午10:08
 */

namespace App\Method;


use App\Constants;
use App\ErrorCode;
use App\Jobs\PushBookingUpdateJob;
use App\Jobs\PushCustomerJob;
use App\Jobs\PushDriverJob;
use App\Model\Booking;
use App\Model\BookingAirline;
use App\Model\BookingChangeHistory;
use App\Model\BookingTransactionHistory;
use App\Model\Calendar;
use App\Model\CompanyAnSetting;
use App\Model\CompanySetting;
use App\Model\Offer;
use App\Model\Order;
use App\PushMsg;
use Illuminate\Support\Facades\DB;
use App\Method\GeoLocationAlgorithm;

class ChangeBookingMatchAlgorithm extends OfferChangeBookingMatchAlgorithm
{
    private static $_instance;

    /**
     * OrderStateAlgorithm constructor.
     */
    private function __construct()
    {
    }

    /**
     * @return ChangeBookingMatchAlgorithm
     */
    public static function getAlgorithm()
    {
        if (self::$_instance == null) {
            self::$_instance = new ChangeBookingMatchAlgorithm();
        }
        return self::$_instance;
    }


    /**
     * 匹配booking可用的offer
     * @param $company_id
     * @param $type
     * @param $d_lat
     * @param $d_lng
     * @param $d_is_airport
     * @param $a_lat
     * @param $a_lng
     * @param $a_is_airport
     * @param $estimate_distance
     * @param $estimate_duration
     * @param $appointed_time
     * @param $token
     * @param $booking_id
     * @return string
     * @throws \Exception
     */
    public function bookingsCheckOffers($company_id,
                                        $type,
                                        $d_lat, $d_lng, $d_is_airport,
                                        $a_lat, $a_lng, $a_is_airport,
                                        $estimate_distance, $estimate_duration,
                                        $appointed_time,
                                        $token,
                                        $booking_id)
    {
        $booking = Booking::leftJoin('booking_transaction_histories as bth ','bth.booking_id',"=","bookings.id")
            ->where("bookings.id", $booking_id)
            ->where(function ($query) use ($company_id){
                $query->where(function ($query) use ($company_id) {
                    $query->where('bookings.exe_com_id', $company_id);
                })
                ->orWhere(function ($query) use ($company_id) {
                    $query->where('bookings.company_id', $company_id)
                        ->where('bookings.reject', Booking::REJECT_TYPE_REJECT);
                });
            })
            ->select(
                "bookings.*",
                "bth.ccy"
            )
            ->first();
        if (empty($booking)) {
            throw new \Exception(ErrorCode::errorAdminUnauthorizedOperation());
        }
        $anSetting = CompanyAnSetting::where('company_id', $company_id)->first();
        $type = ($type == null || $booking->type == $type) ? $booking->type : $type;
        $booking_car_id = $booking->car_id;
        $booking_driver_id = $booking->driver_id;
        $d_lat = $d_lat == null ? $booking->d_lat : $d_lat;
        $d_lng = $d_lng == null ? $booking->d_lng : $d_lng;
        $a_lat = $a_lat == null ? $booking->a_lat : $a_lat;
        $a_lng = $a_lng == null ? $booking->a_lng : $a_lng;
        $estimate_duration = $estimate_duration == null ? $booking->estimate_time : $estimate_duration;
        $estimate_distance = $estimate_distance == null ? $booking->estimate_distance : $estimate_distance;
        $appointed_time = $appointed_time == null ? strtotime($booking->appointed_at) : $appointed_time;
        $d_is_airport = $d_is_airport == null ? $booking->d_is_airport : $d_is_airport;
        $a_is_airport = $a_is_airport == null ? $booking->a_is_airport : $a_is_airport;

        $temp_a_lat = $type == Offer::SHOW_TYPE_LONG ? $a_lat : $d_lat;
        $temp_a_lng = $type == Offer::SHOW_TYPE_LONG ? $a_lng : $d_lng;
        $booking_car = json_decode($booking->car_data, true);
        $ccy = $booking->ccy;
        if (strtotime($booking->appointed_at) == $appointed_time &&
            $booking->estimate_time == $estimate_duration
        ) {
            $check_car_pass = true;
            $check_driver_pass = true;
        } else {
            $check_car_pass = !$this->bookingMatch($booking_car['pre_time'], 'car', $booking->car_id,
                    $appointed_time, $estimate_duration,
                    $d_lat, $d_lng, $temp_a_lat, $temp_a_lng, $booking_id) ||
                !$this->checkCarSpecifiedTimeAvailable($booking_car_id, $appointed_time, $estimate_duration);
            $check_driver_pass = !$this->bookingMatch($booking_car['pre_time'], 'driver', $booking->driver_id,
                    $appointed_time, $estimate_duration,
                    $d_lat, $d_lng, $temp_a_lat, $temp_a_lng, $booking_id) ||
                !$this->checkDriverSpecifiedTimeAvailable($booking_driver_id, $appointed_time, $estimate_duration);
        }
        if ($type == Booking::CHECK_TYPE_DISTANCE) {

            if (!is_numeric($d_lat) ||
                $d_lat > 90 ||
                $d_lat < -90
            ) {
                throw new \Exception(ErrorCode::errorParam('d_lat'));
            }
            if (!is_numeric($d_lng) ||
                $d_lng > 180 ||
                $d_lng < -180
            ) {
                throw new \Exception(ErrorCode::errorParam('d_lng'));
            }
            if (!is_numeric($a_lat) ||
                $a_lat > 90 ||
                $a_lat < -90
            ) {
                throw new \Exception(ErrorCode::errorParam('a_lat'));
            }
            if (!is_numeric($a_lng) ||
                $a_lng > 180 ||
                $a_lng < -180
            ) {
                throw new \Exception(ErrorCode::errorParam('a_lng'));
            }
            $offersResult = $this->matchBookingOfferP2PMatch(
                $company_id, $booking->exe_com_id, $booking->unit,$booking->reject == Booking::REJECT_TYPE_REJECT,
                $d_lat, $d_lng, $d_is_airport,
                $a_lat, $a_lng, $a_is_airport,
                $appointed_time, $estimate_duration, $estimate_distance,
                $check_car_pass, $check_driver_pass,
                $booking_id, $booking_driver_id, $booking_car,
                $booking->base_cost, $anSetting, $company_id != $booking->exe_com_id, $token,$ccy);
        } elseif ($type == Booking::CHECK_TYPE_HOURLY) {
            $offersResult = $this->matchBookingOfferHourlyMatch(
                $company_id, $booking->exe_com_id,$booking->unit, $booking->reject == Booking::REJECT_TYPE_REJECT,
                $d_lat, $d_lng, $d_is_airport,
                $appointed_time, $estimate_duration, $estimate_distance,
                $check_car_pass, $check_driver_pass,
                $booking_id, $booking_driver_id, $booking_car,
                $booking->base_cost, $anSetting, $company_id != $booking->exe_com_id, $token,$ccy);
        } elseif ($type == Booking::CHECK_TYPE_CUSTOM) {
            throw new \Exception(ErrorCode::errorToEditCustomBooking());
//                $this->checkCustomerQuoteInBooking($company_id,$token,$appointed_time,$estimate_duration,$pre_time,$booking->driver_id,$booking->car_id,$booking_id);
        } else {
            throw new \Exception(ErrorCode::errorParam("unknown type"));
        }
        return $offersResult;
    }

    public function changeBookings($company_id, $booking_id, $param, $token, $admin_id)
    {

        //MARK 修改订单时注意修改统计信息
        $booking = Booking::where('id', $booking_id)
            ->where(function ($query) use ($company_id){
                $query->where(function ($query) use ($company_id) {
                    $query->where('exe_com_id', $company_id);
                })
                    ->orWhere(function ($query) use ($company_id) {
                        $query->where('company_id', $company_id)
                            ->where('reject', Booking::REJECT_TYPE_REJECT);
                    });
            })
            ->first();
        $copyBooking = json_encode($booking);
        if (empty($booking)) {
            throw new \Exception(ErrorCode::errorAdminUnauthorizedOperation());
        }
        if ($booking->type == Booking::CHECK_TYPE_CUSTOM) {
            throw new \Exception(ErrorCode::errorToEditCustomBooking());
        }
        $check = Booking::leftjoin('orders', 'orders.booking_id', '=', 'bookings.id')
            ->where('bookings.id', $booking_id)
            ->where('orders.trip_state', '>', Order::TRIP_STATE_WAIT_TO_DEPARTURE)
            ->first();
        if (!empty($check)) {
            throw new \Exception(ErrorCode::errorOrderHasBeenComplied());
        }
        $param = json_decode($param, true);
        $type = isset($param['type']) ? $param['type'] : null;
        $offer_id = isset($param['offer_id']) ? $param['offer_id'] : null;
        $car_id = isset($param['car_id']) ? $param['car_id'] : null;
        $driver_id = isset($param['driver_id']) ? $param['driver_id'] : null;
        $note = isset($param['note']) ? $param['note'] : "";
        $appointed_time = isset($param['appointed_time']) ? $param['appointed_time'] : null;
        $estimate_duration = isset($param['estimate_duration']) ? $param['estimate_duration'] : null;
        $d_lng = isset($param['d_lng']) ? $param['d_lng'] : null;
        $d_lat = isset($param['d_lat']) ? $param['d_lat'] : null;
        $unit = isset($param['unit']) ? $param['unit'] : $booking->unit;
        $estimate_distance = isset($param['estimate_distance']) ? $param['estimate_distance'] : null;
        $a_lat = isset($param['a_lat']) ? $param['a_lat'] : null;
        $a_lng = isset($param['a_lng']) ? $param['a_lng'] : null;
        $a_address = isset($param['a_address']) ? $param['a_address'] : null;
        $a_address = is_null($a_address) ? null : (is_array($a_address) ? json_encode($a_address) : $a_address);
        $a_is_airport = isset($param['a_is_airport']) ? $param['a_is_airport'] : null;
        $d_address = isset($param['d_address']) ? $param['d_address'] : null;
        $d_address = is_null($d_address) ? null : (is_array($d_address) ? json_encode($d_address) : $d_address);
        $d_is_airport = isset($param['d_is_airport']) ? $param['d_is_airport'] : null;
        $post_options = isset($param['options']) ? $param['options'] : null;
        $freeFee = isset($param['free_fee']) ? $param['free_fee'] : 0;
        $passengerNames = isset($param['passenger_names']) ? $param['passenger_names'] : null;
        $passengerCount = isset($param['passenger_count']) ? $param['passenger_count'] : null;
        $a_airline = isset($param['a_airline']) ? $param['a_airline'] : null;
        $d_airline = isset($param['d_airline']) ? $param['d_airline'] : null;
        $a_flight = isset($param['a_flight']) ? $param['a_flight'] : null;
        $d_flight = isset($param['d_flight']) ? $param['d_flight'] : null;
        $a_address = GeoLocationAlgorithm::getInstance()->simplifyAddress($a_address);
        $d_address = GeoLocationAlgorithm::getInstance()->simplifyAddress($d_address);
        if (
            is_null($type) && is_null($offer_id) && is_null($driver_id) &&
            is_null($car_id) && is_null($estimate_duration) &&
            is_null($d_lng) && is_null($d_lat) && is_null($d_address) && is_null($a_is_airport) &&
            is_null($a_lng) && is_null($a_lat) && is_null($a_address) && is_null($d_is_airport) &&
            is_null($appointed_time) && is_null($post_options) && is_null($passengerNames)
            && is_null($passengerCount) &&
            is_null($a_airline) && is_null($a_flight) && is_null($d_airline) && is_null($d_flight)
        ) {
            throw new \Exception(ErrorCode::errorMissingParam());
        }
        if (
            !is_null($a_airline) || !is_null($a_flight) ||
            !is_null($d_airline) || !is_null($d_flight)
        ) {
            $flight = BookingAirline::where('booking_id', $booking_id)->first();
            if (!is_null($a_airline)) {
                $flight->a_airline = $a_airline;
            }
            if (!is_null($a_flight)) {
                $flight->a_flight = $a_flight;
            }
            if (!is_null($d_airline)) {
                $flight->d_airline = $d_airline;
            }
            if (!is_null($d_flight)) {
                $flight->d_flight = $d_flight;
            }
            $flight->save();
        }


        if (!is_numeric($freeFee)) {
            throw new \Exception(ErrorCode::errorParam('free_fee'));
        }

        if (!is_null($passengerNames)) {
            $booking->passenger_names = $passengerNames;
        }
        if (!is_null($passengerCount)) {
            if (!is_numeric($passengerCount) ||
                $passengerCount < 0
            ) {
                throw new \Exception(ErrorCode::errorParam('passenger count'));
            }
            $booking->passenger_count = $passengerCount;
        }
        if (!is_null($note)) {
            $booking->message = $note;
        }
        $type = ($type == null) ? $booking->type : $type;
        $offer_id = ($offer_id == null) ? $booking->offer_id : $offer_id;
        $car_id = ($car_id == null) ? $booking->car_id : $car_id;
        $driver_id = ($driver_id == null) ? $booking->driver_id : $driver_id;
        $appointed_time = ($appointed_time == null) ? strtotime($booking->appointed_at) : $appointed_time;
        $estimate_duration = ($estimate_duration == null) ? $booking->estimate_time : $estimate_duration;
        $d_lng = ($d_lng == null) ? $booking->d_lng : $d_lng;
        $d_lat = ($d_lat == null) ? $booking->d_lat : $d_lat;
        $estimate_distance = ($estimate_distance == null) ? $booking->estimate_distance : $estimate_distance;
        $a_lat = ($a_lat == null) ? $booking->a_lat : $a_lat;
        $a_lng = ($a_lng == null) ? $booking->a_lng : $a_lng;
        $a_address = ($a_address == null) ? $booking->a_address : $a_address;
        $a_is_airport = ($a_is_airport == null) ? $booking->a_is_airport : $a_is_airport;
        $d_address = ($d_address == null) ? $booking->d_address : $d_address;
        $d_is_airport = ($d_is_airport == null) ? $booking->d_is_airport : $d_is_airport;
//                    $post_options = ($post_options==null)?$booking->post_options:$post_options;
        //判断type
        if (!is_null($type)) {
            //type是否符合规范
            if (empty($type) || !is_numeric($type) || ($type != 1 && $type != 2)) {
                throw new \Exception(ErrorCode::errorParam('type'));
            } //符合规范
            else {
                $orderType = $type;
                //type 类型是否变化,变化重新匹配,并返回结果
                if (($booking->type) != ($orderType)) {
//                            echo 'changed by type'.$booking->type."change type is ".$type;
                    return $this->changeBookingsByType($company_id, $type, $offer_id,
                        $car_id, $driver_id,$unit,
                        $estimate_distance, $estimate_duration,
                        $d_lng, $d_lat, $d_address, $d_is_airport,
                        $a_lng, $a_lat, $a_address, $a_is_airport,
                        $appointed_time, $note,
                        $token, $booking, $freeFee,$copyBooking,$admin_id);
                }
            }
        }


        //判断offer_id
        if (!is_null($offer_id)) {
            //offer_id是否符合规范
            if (empty($offer_id) || !is_numeric($offer_id)) {
                throw new \Exception(ErrorCode::errorParam('offer'));
            } else {
                //offer_id是否有变化,有变化,重新匹配
//                        echo 'booking offer is '.$booking->offer_id.' changed offer id is '.$offer_id;
                if ($booking->offer_id != $offer_id) {
                    return $this->changeBookingsByType($company_id, $type, $offer_id,
                        $car_id, $driver_id,$unit,
                        $estimate_distance, $estimate_duration,
                        $d_lng, $d_lat, $d_address, $d_is_airport,
                        $a_lng, $a_lat, $a_address, $a_is_airport,
                        $appointed_time, $note,
                        $token, $booking, $freeFee,$copyBooking,$admin_id);
                }
            }
        }


        $old_driver_id = $booking->driver_id;
        //上车下车地点有变化
        if (is_null($d_lng) && is_null($d_lat) &&
            is_null($a_lat) && is_null($a_lng)
        ) {
            //nothing to do
        } else {
            if (!is_null($d_lng)) {
                if ((empty($d_lng) || !is_numeric($d_lng) || $d_lng < -180 || $d_lng > 180)) {
                    throw new \Exception(ErrorCode::errorParam('d_lng is error format'));
                } else {
                }
            } else {
                $d_lng = $booking->d_lng;
            }
            if (!is_null($d_lat)) {
                if ((empty($d_lat) || !is_numeric($d_lat) || $d_lat < -90 || $d_lat > 90)) {
                    throw new \Exception(ErrorCode::errorParam('d_lat is error format'));
                } else {
                }
            } else {
                $d_lat = $booking->d_lat;
            }
            if (!is_null($a_lng)) {
                if ((empty($a_lng) || !is_numeric($a_lng) || $a_lng < -180 || $a_lng > 180)) {
                    throw new \Exception(ErrorCode::errorParam('a_lng is error format++++++'));
                } else {
                }
            } else {
                $a_lng = $booking->a_lng;
            }

            if (!is_null($a_lat)) {
                if ((empty($a_lat) || !is_numeric($a_lat) || $a_lat < -90 || $a_lat > 90)) {
                    throw new \Exception(ErrorCode::errorParam('a_lat is error format'));
                } else {
                }
            } else {
                $a_lat = $booking->a_lat;
            }

            if (is_null($estimate_duration) || empty($estimate_duration)
                || !is_numeric($estimate_duration) || $estimate_duration < 0
            ) {
                throw new \Exception(ErrorCode::errorParam('estimate_duration error'));
            }

            if (
                $d_lng != $booking->d_lng || $d_lat != $booking->d_lat ||
                $a_lat != $booking->a_lat || $a_lng != $booking->a_lng
            ) {
                if ($type == Booking::CHECK_TYPE_DISTANCE) {
                    if (is_null($offer_id) || is_null($estimate_duration) ||
                        is_null($d_lng) || is_null($d_lat) ||
                        is_null($a_lng) || is_null($a_lat) ||
                        is_null($estimate_distance)
                    ) {
                        throw new \Exception(ErrorCode::errorMissingParam());
                    }
                    if (is_null($estimate_distance) || empty($estimate_distance)
                        || !is_numeric($estimate_distance) || $estimate_distance < 0
                    ) {
                        throw new \Exception(ErrorCode::errorParam('estimate_distance error'));
                    }
                    $offer = $this->offerP2PMatch(
                        $company_id,$booking->unit,
                        $d_lat, $d_lng, $a_lat, $a_lng,
                        $estimate_distance, $estimate_duration,
                        $offer_id, 0, 0);
                } elseif ($type == Booking::CHECK_TYPE_HOURLY) {
                    if (is_null($offer_id) || is_null($estimate_duration) ||
                        is_null($d_lng) || is_null($d_lat)
                    ) {
                        throw new \Exception(ErrorCode::errorMissingParam());
                    }
                    $offer = $this->offerHourlyMatch($company_id,$booking->unit,
                        $d_lat, $d_lng,
                        $estimate_duration, $appointed_time,
                        $offer_id, 0, 0);
                } else {
                    throw new \Exception('Tommy Lee code bug');
                }
                if (empty($offer)) {
                    throw new \Exception(ErrorCode::errorParam('changed location could not use for this offer'));
                }
                if (!$this->checkOfferSpecifiedTimeAvailable($offer_id, $appointed_time, $estimate_duration)
                ) {
                    throw new \Exception(ErrorCode::errorOfferUseAppointedTime());
                }
                $booking->offer_data = json_encode($offer);
                if (!is_null($a_address)) {
                    $booking->a_address = $a_address;
                }
                if (!is_null($d_address)) {
                    $booking->d_address = $d_address;
                }
                if (!is_null($d_lng)) {
                    $booking->d_lng = $d_lng;
                }
                if (!is_null($d_lat)) {
                    $booking->d_lat = $d_lat;
                }
                if (!is_null($a_lng)) {
                    $booking->a_lng = $a_lng;
                }
                if (!is_null($a_lat)) {
                    $booking->a_lat = $a_lat;
                }

                $booking->estimate_time = $estimate_duration;
                $booking->estimate_distance = $estimate_distance;

                $base_cost = PaymentMethod::offerPriceSettlement($offer->cost_min,
                    $offer->calc_method,
                    $estimate_duration,
                    $estimate_distance,
                    $offer->prices,
                    $unit,isset($booking->offer_data->unit)?isset($booking->offer_data->unit):$booking->unit,
                    $offer->d_is_airport,
                    $offer->d_port_price,
                    $offer->a_is_airport,
                    $offer->a_port_price
                );
                $booking->base_cost = $base_cost;
                $booking->total_cost = $base_cost + $booking->option_cost;

            }
        }

        $temp_a_lat = is_null($booking->a_lat) ? $d_lat : $booking->a_lat;
        $temp_a_lng = is_null($booking->a_lng) ? $d_lng : $booking->a_lng;
        $offer = json_decode($booking->offer_data);
        //修改时间
        if (!is_null($appointed_time)) {
            if (empty($appointed_time) || !is_numeric($appointed_time) ||
                $appointed_time < time()
            ) {
                throw new \Exception(ErrorCode::errorParam('appointed time is in error format or too close to now '));
            } else {
                //有修改匹配车,匹配司机,匹配offer
                if ($appointed_time != strtotime($booking->appointed_at)) {
                    //匹配现有offer
                    if (!$this->checkOfferSpecifiedTimeAvailable($offer_id, $appointed_time, $booking->estimate_time)
                    ) {
                        throw new \Exception(ErrorCode::errorOfferUseAppointedTime());
                    }
                    if (is_null($driver_id)) {
                        $driver_id = $booking->driver_id;
                    } else {
                        if (empty($driver_id) || !is_numeric($driver_id)) {
                            throw new \Exception(ErrorCode::errorParam('driver_id'));
                        } else {
                            $booking->driver_id = $driver_id;
                        }
                    }
                    //匹配司机
                    if (!$this->checkDriverSpecifiedTimeAvailableInBooking($company_id, $driver_id,
                        $appointed_time, $estimate_duration, $booking_id)
                    ) {
                        throw new \Exception(ErrorCode::errorOfferUseDriver());
                    }


                    if (is_null($car_id)) {
                        $car_id = $booking->car_id;
                    } else {
                        if (empty($car_id) || !is_numeric($car_id)) {
                            throw new \Exception(ErrorCode::errorParam('car_id'));
                        } else {
                            $booking->car_id = $car_id;
                        }
                    }
                    //匹配汽车
                    if (!$this->checkCarSpecifiedTimeAvailableInBooking($company_id, $car_id,
                        $appointed_time, $estimate_duration, $booking->id)
                    ) {
                        throw new \Exception(ErrorCode::errorOfferUseCar());
                    }

                    if(is_null($booking->a_lat) || is_null($booking->a_lng))
                    {
                        $timeInfo = GeoLocationAlgorithm::getInstance()
                        ->getLocationTime($booking->d_lat,$booking->d_lng,$booking->appointed_at);
                    }else
                    {
                        $timeInfo = GeoLocationAlgorithm::getInstance()
                        ->getLocationTime($booking->a_lat,$booking->a_lng,$booking->appointed_at);
                    }

                    $timezone = isset($timeInfo->timeZoneId)?$timeInfo->timeZoneId:"UTC";

                    $myDateTime = new \DateTime($appointed_time, $timezone);

                    $myDateTime->setTimeZone(new \DateTimeZone("UTC"))->format('Y-m-d H:i:s');

                    $booking->appointed_at = $myDateTime;
                    //$booking->appointed_at = MethodAlgorithm::formatTimestampToDate($appointed_time);
                }
            }
        } else {
            $appointed_time = strtotime($booking->appointed_at);
        }
        if (is_null($car_id)) {
        } else {
            if (empty($car_id) || !is_numeric($car_id)) {
                throw new \Exception(ErrorCode::errorParam('car_id'));
            } else {
                if ($car_id != $booking->car_id) {
                    $car = $this->getCars($car_id, $offer_id, $company_id, $token);
                    if (empty($car)) {
                        throw new \Exception(ErrorCode::errorCarUseAppointedTime());
                    }
                    //匹配汽车
                    if (
                        !$this->bookingMatch($car->pre_time, 'car', $car_id, $appointed_time,
                            $estimate_duration, $d_lat, $d_lng,
                            $temp_a_lat, $temp_a_lng, $booking_id) ||
                        !$this->checkCarSpecifiedTimeAvailable($car_id, $appointed_time, $estimate_duration)
                    ) {
                        throw new \Exception(ErrorCode::errorOfferUseCar());
                    }
                    $booking->car_id = $car_id;
                    $booking->car_data = json_encode($car);
                }
            }
        }

        $car = json_decode($booking->car_data, true);
        if (is_null($driver_id)) {
        } else {
            if (empty($driver_id) || !is_numeric($driver_id)) {
                throw new \Exception(ErrorCode::errorParam('driver_id'));
            } else {
                if ($driver_id != $booking->driver_id) {
                    $driver = $this->getDriver($driver_id, $offer_id, $car_id, $token);
                    if (empty($driver)) {
                        throw new \Exception(ErrorCode::errorDriverUseAppointedTime());
                    }
                    //匹配司机
                    if (
                        !$this->bookingMatch($car['pre_time'], 'driver', $driver_id,
                            $appointed_time, $estimate_duration,
                            $d_lat, $d_lng, $temp_a_lat, $temp_a_lng, $booking_id) ||
                        !$this->checkDriverSpecifiedTimeAvailable($driver_id, $appointed_time, $estimate_duration)
                    ) {
                        throw new \Exception(ErrorCode::errorOfferUseDriver());
                    }
                    $booking->driver_id = $driver_id;
                    $booking->driver_data = json_encode($driver);
                }

            }
        }


//                    if (is_null($post_options)) {
//                        //数据不做变更
//                    } else {
//                        if (!is_array($post_options)) {
//                            $booking->options = "[]";
//                        } else {
//                            $option_date = array();
//                            $option_cost = 0.00;
//                            $this->getOfferOptions($offer);
//                            $options = $offer->options;
////                        echo $options;
//                            foreach ($post_options as $post_option) {
//                                foreach ($options as $option) {
//                                    if ($option->type == 'GROUP') {
//                                        foreach ($option->group as $item) {
//                                            if ($post_option['option_id'] == $item->option_id) {
//                                                $count = $post_option['count'];
//                                                if ($count > $item->add_max) {
//                                                    throw new \Exception(ErrorCode::errorParam($item->name . " exceeded the maximum number of available"));
//                                                }
//                                                $option_cost += $item->price * $post_option['count'];
//                                                $tempOption = ["option_name" => $item->name, "price" => $item->price, "count" => $count];
//                                                array_push($option_date, $tempOption);
//                                            }
//
//                                        }
//                                    } else {
//                                        if ($post_option['option_id'] == $option->option_id) {
//                                            $count = $post_option['count'];
//                                            if ($count > $option->add_max) {
//                                                throw new \Exception(ErrorCode::errorParam($option->name . " exceeded the maximum number of available"));
//                                            }
//                                            $option_cost += $option->price * $post_option['count'];
//                                            $tempOption = ["option_name" => $option->name, "price" => $option->price, "count" => $count];
//                                            array_push($option_date, $tempOption);
//                                        }
//                                    }
//                                }
//                            }
//                            $booking->option_data = json_encode($option_date);
//                            $booking->option_cost = $option_cost;
//                            $booking->total_cost = $option_cost + $booking->base_cost;
//                        }
//                    }


        dispatch(new PushBookingUpdateJob($booking->customer_id,$old_driver_id,$driver_id));
        
        $booking->save();
        $driver = $this->getDriver($booking->driver_id, $booking->offer_id, $booking->car_id, $token);
        $car = $this->getCars($booking->car_id, $booking->offer_id, $company_id, $token);
        BookingCalendarAlgorithm::getCalendar()->changeExistCalendarEvent(
            $booking_id,
            $driver, $car,
            $booking->d_address, $booking->a_address,
            $appointed_time, $booking->estimate_time,
            $booking->customer_id, $booking->company_id,
            $booking->total_cost, $type);
        BookingChangeHistory::create(
            [
                "company_id" => $company_id,
                "admin_id" => $admin_id,
                "booking_id" => $booking_id,
                "booking_info" => $copyBooking,
                "action_type"=>BookingChangeHistory::ACTION_TYPE_EDIT
            ]
        );

        return $booking;

    }

    private function changeBookingsByType($company_id, $type, $offer_id,
                                          $car_id, $driver_id,$unit,
                                          $estimate_distance, $estimate_duration,
                                          $d_lng, $d_lat, $d_address, $d_is_airport,
                                          $a_lng, $a_lat, $a_address, $a_is_airport,
                                          $appointed_time, $note,
                                          $token, $booking, $free_fee,$copyBooking,$admin_id)
    {
        $old_driver_id = $booking->driver_id;
        $temp_a_lng = !empty($a_lng) ? $a_lng : $d_lng;
        $temp_a_lat = !empty($a_lat) ? $a_lat : $d_lat;
        if (!is_numeric($d_is_airport) ||
            ($d_is_airport != Offer::IS_AIRPORT &&
                $d_is_airport != Offer::NOT_AIRPORT)
        ) {
            throw new \Exception(ErrorCode::errorParam("d_is_airport"));
        }
        if (!is_numeric($a_is_airport) ||
            ($a_is_airport != Offer::IS_AIRPORT &&
                $a_is_airport != Offer::NOT_AIRPORT)
        ) {
            throw new \Exception(ErrorCode::errorParam("a_is_airport"));
        }
        if (empty($type) || ($type != 1 && $type != 2)) {
            throw new \Exception(ErrorCode::errorParam('type'));
        }
        if (empty($appointed_time) || !is_numeric($appointed_time) || $appointed_time < time()) {
            throw new \Exception(ErrorCode::errorOfferUseAppointedTime());
        }
        if (empty($d_address)) {
            throw new \Exception(ErrorCode::errorMissingParam('d_address'));
        }
        if (!is_numeric($d_lng) || $d_lng < -180 || $d_lng > 180) {
            throw new \Exception(ErrorCode::errorParam('d_lng'));
        }
        if (!is_numeric($d_lat) || $d_lat < -90 || $d_lat > 90) {
            throw new \Exception(ErrorCode::errorParam('d_lat'));
        }
        if ($type == Booking::CHECK_TYPE_DISTANCE) {
            if (empty($a_address)) {
                throw new \Exception(ErrorCode::errorMissingParam('a_address'));
            }
            if (!is_numeric($estimate_distance) || $estimate_distance < 0) {
                throw new \Exception(ErrorCode::errorParam('estimate_distance'));
            }
            if (!is_numeric($a_lng) || $a_lng < -180 || $a_lng > 180) {
                throw new \Exception(ErrorCode::errorParam('a_lng'));
            }
            if (!is_numeric($a_lat) || $a_lat < -90 || $a_lat > 90) {
                throw new \Exception(ErrorCode::errorParam('a_lat'));
            }

            $offer = $this->offerP2PMatch($company_id,$unit,
                $d_lat, $d_lng, $a_lat, $a_lng,
                $estimate_distance, $appointed_time,
                $offer_id, 0, 0);
        } elseif ($type == Booking::CHECK_TYPE_HOURLY) {
            $offer = $this->offerHourlyMatch($company_id,$unit,
                $d_lat, $d_lng,
                $estimate_duration,
                $appointed_time,
                $offer_id, 0, 0);
        } elseif ($type == Booking::CHECK_TYPE_CUSTOM) {
            throw new \Exception(ErrorCode::errorChangeToCustomBooking());
        } else {
            throw new \Exception('Tommy Lee code bug at changeBookingsByType');
        }
        if (empty($offer) || count($offer) == 0) {
            throw new \Exception(ErrorCode::errorOfferUse());
        }
        $offer = $offer[0];
        $trip_cost = PaymentMethod::offerPriceSettlement($offer->cost_min,
            $offer->calc_method, $estimate_duration,
            $estimate_distance, $offer->prices,
            $unit,$offer->unit,
            $booking->d_is_airport,
            $offer->d_port_price,
            $booking->a_is_airport,
            $offer->a_port_price
        );
//        echo "trip cost is ".$trip_cost." base cost is ".$booking->base_cost." free fee ".($free_fee);
//        echo "settle is ".($booking->base_cost + $free_fee) ;
        $check_free = $booking->base_cost + $free_fee;
        if (round($check_free, 2) != round($trip_cost, 2)) {
            throw new \Exception(ErrorCode::errorParam("offer price was changed " . $trip_cost));
        }
        //记录option
//        $post_options = $param['options'];
//        $option_date = array();
//        $this->getOfferOptions($offer);
//        $options = $offer->options;
//        $option_cost = 0.00;
//        $tempOption = array();
//        foreach ($post_options as $post_option) {
//            foreach ($options as $option) {
//                if ($option->type == 'GROUP') {
//                    foreach ($option->group as $item) {
//                        if ($post_option['option_id'] == $item->option_id) {
//                            $count = $post_option['count'];
//                            if ($count > $item->add_max) {
//                                throw new \Exception(ErrorCode::errorParam($item->name . " exceeded the maximum number of available"));
//                            }
//                            $option_cost += $item->price * $post_option['count'];
//                            $tempOption = ["option_name" => $item->name, "price" => $item->price, "count" => $count];
//                            array_push($option_date, $tempOption);
//                        }
//
//                    }
//                } else {
//                    if ($post_option['option_id'] == $option->option_id) {
//                        $count = $post_option['count'];
//                        if ($count > $option->add_max) {
//                            throw new \Exception(ErrorCode::errorParam($option->name . " exceeded the maximum number of available"));
//                        }
//                        $option_cost += $option->price * $post_option['count'];
//                        $tempOption = ["option_name" => $option->name, "price" => $option->price, "count" => $count];
//                        array_push($option_date, $tempOption);
//                    }
//                }
//            }
//        }


        //检查车辆/司机/offer是否可用
        if (
        !$this->checkOfferSpecifiedTimeAvailable($offer_id, $appointed_time, $estimate_duration)
        ) {
            throw new \Exception(ErrorCode::errorOfferUseAppointedTime());
        }


        $car = $this->getCars($car_id, $offer_id, $company_id, $token);
        if (empty($car)) {
            throw new \Exception(ErrorCode::errorNotExist('car'));
        }
        $driver = $this->getDriver($driver_id, $offer_id, $car_id, $token);
        if (empty($driver)) {
            throw new \Exception(ErrorCode::errorNotExist('driver'));
        }
        //匹配司机/车 日程
        if (
            !$this->bookingMatch($car->pre_time, 'driver', $driver->driver_id, $appointed_time, $estimate_duration, $d_lat, $d_lng, $temp_a_lat, $temp_a_lng) &&
            !$this->checkDriverSpecifiedTimeAvailableInBooking($company_id, $driver_id,
                $appointed_time, $estimate_duration, $booking->id)
        ) {
            throw new \Exception(ErrorCode::errorOfferUseDriver());
        }

        if (
            !$this->bookingMatch($car->pre_time, 'car', $car_id, $appointed_time, $estimate_duration, $d_lat, $d_lng, $temp_a_lat, $temp_a_lng, $booking->id) ||
            !$this->checkCarSpecifiedTimeAvailableInBooking(
                $company_id, $car_id,
                $appointed_time,
                $estimate_duration, $booking->id)
        ) {
            throw new \Exception(ErrorCode::errorOfferUseCar());
        }

        //变更event


        BookingCalendarAlgorithm::getCalendar()->
        changeExistCalendarEvent(
            $booking->id,
            $driver, $car,
            $d_address, $a_address,
            $appointed_time, $estimate_duration,
            $booking->customer_id, $booking->company_id,
            $booking->total_cost, $type);
        $booking->exe_com_id = $offer->company_id;
        $booking->car_id = $car_id;
        $booking->driver_id = $driver_id;
        $booking->type = $type;
        $booking->appointed_at = MethodAlgorithm::formatTimestampToDate($appointed_time);
        $booking->d_lat = $d_lat;
        $booking->d_lng = $d_lng;
        $booking->estimate_time = $estimate_duration;
        $booking->estimate_distance = $estimate_distance;
        $booking->a_lat = $a_lat;
        $booking->a_lng = $a_lng;
        $booking->offer_id = $offer_id;
//        $booking->base_cost = $trip_cost;
//        $booking->total_cost = $trip_cost + $option_cost;
//        $booking->option_cost = $option_cost;
        $booking->free_fee = $free_fee;
        $booking->message = $note;
        $booking->reject = Booking::REJECT_TYPE_NORMAL;
        $booking->d_address = $d_address;
        $booking->a_address = $a_address;
        $booking->driver_data = json_encode($driver);
        $booking->car_data = json_encode($car);
//        $booking->option_data = json_encode($option_date);
        $booking->offer_data = json_encode($offer);
        $booking->save();


        dispatch(new PushBookingUpdateJob($booking->customer_id,$old_driver_id,$driver_id));

        BookingChangeHistory::create(
            [
                "company_id" => $company_id,
                "admin_id" => $admin_id,
                "booking_id" => $booking->id,
                "booking_info" => $copyBooking,
                "action_type"=>BookingChangeHistory::ACTION_TYPE_EDIT
            ]
        );
        $booking->appointed_at = $appointed_time;
        $booking->driver = $driver;
        $booking->car = $car;
        return $booking;
    }

    private function checkCarSpecifiedTimeAvailableInBooking(
        $company_id, $car_id,
        $appointed_time, $duration,
        $booking_id)
    {
        $dst = MethodAlgorithm::checkDstForCompany($company_id, $appointed_time);
        $calendar = Calendar::
        where('owner_id', $car_id)
            ->where('type', Calendar::CAR_TYPE)
            ->select("calendars.id",
                DB::raw("case when {$dst}=1
                then
                calendars.dst_routine
                else
                calendars.routine
                end as routine
                "),
                'owner_id',
                'type')
            ->first();
        if (empty($calendar)) {
            return false;
        }
        $calendar->creator_id = $booking_id;
        return $this->matchRoutineAndAppointTime($calendar, $appointed_time, $duration, true);
    }

    private function checkDriverSpecifiedTimeAvailableInBooking($company_id, $driver_id, $appointed_time, $duration, $booking_id)
    {
        $dst = MethodAlgorithm::checkDstForCompany($company_id, $appointed_time);
        $offerCalendar = Calendar::
        where('calendars.owner_id', $driver_id)
            ->where('calendars.type', Calendar::DRIVER_TYPE)
            ->select("calendars.id",
                DB::raw("case when {$dst}=1
                then
                calendars.dst_routine
                else
                calendars.routine
                end as routine
                "),
                'owner_id',
                'type')
            ->first();
        if (empty($offerCalendar)) {
            return false;
        }
        $offerCalendar->creator_id = $booking_id;
        return $this->matchRoutineAndAppointTime($offerCalendar, $appointed_time, $duration, true);
    }
}