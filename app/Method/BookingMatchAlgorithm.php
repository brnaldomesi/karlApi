<?php
/**
 * Created by PhpStorm.
 * User: liqihai
 * Date: 16/9/2
 * Time: ä¸Šåˆ10:34
 */

namespace App\Method;

use App\Constants;
use App\ErrorCode;
use App\Jobs\BookingCreateStatisticJob;
use App\Jobs\PushBookingSuccessJob;
use App\Jobs\SendEmailAdminBookingJob;
use App\Jobs\SendEmailCustomerBookingJob;
use App\Jobs\SendEmailCustomQuoteDetermineJob;
use App\Jobs\SendEmailAffiliateBookingJob;
use App\Model\Booking;
use App\Model\BookingAirline;
use App\Model\Calendar;
use App\Model\CalendarEvent;
use App\Model\Car;
use App\Model\CompanyAnSetting;
use App\Model\CompanyPayMethod;
use App\Model\CompanySetting;
use App\Model\CreditCard;
use App\Model\Customer;
use App\Model\Driver;
use App\Model\Offer;
use App\Model\OfferDriverCar;
use App\Model\Order;
use App\Model\Coupon;
use App\Model\RunningError;
use App\Model\Onetime_couponHistory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Log;
use App\Method\GeoLocationAlgorithm;

class BookingMatchAlgorithm extends MatchAlgorithm
{
    public function offerP2PSearch($company_id, $unit,
                                   $d_lat, $d_lng, $a_lat, $a_lng,
                                   $estimate_distance,
                                   $appointed_time, $offer_id)
    {
        $offers = $this->offerP2PMatch(
            $company_id, $unit,
            $d_lat, $d_lng, $a_lat, $a_lng,
            $estimate_distance, $appointed_time,
            $offer_id, self::AN_TYPE_NORMAL,
            CompanyAnSetting::COMBINE_DISABLE);
        return count($offers) <= 0 ? null : $offers[0];
    }


    public function offerHourlySearch($company_id, $unit, $d_lat, $d_lng,
                                      $estimate_duration,
                                      $appointed_time, $offer_id)
    {
        $offers = $this->offerHourlyMatch(
            $company_id, $unit, $d_lat, $d_lng,
            $estimate_duration,
            $appointed_time,
            $offer_id,
            self::AN_TYPE_NORMAL,
            CompanyAnSetting::COMBINE_DISABLE);
        return count($offers) <= 0 ? null : $offers[0];
    }

    public function addBookings($company_id, $customer_id, $param, $user, $token)
    {
        try {
            $booking = DB::transaction(function () use ($company_id, $customer_id, $param, $user, $token) {
                $type = isset($param['type']) ? $param['type'] : null;
                $unit = isset($param['unit']) ? $param['unit'] : CompanySetting::UNIT_MI;
                $offer_id = isset($param['offer_id']) ? $param['offer_id'] : null;
                $car_id = isset($param['car_id']) ? $param['car_id'] : null;
                $driver_id = isset($param['driver_id']) ? $param['driver_id'] : "";
                $note = isset($param['note']) ? $param['note'] : "";
                $appointed_time = isset($param['appointed_time']) ? $param['appointed_time'] : null;
                $estimate_duration = isset($param['estimate_duration']) ? $param['estimate_duration'] : null;
                $cost = isset($param['cost']) ? $param['cost'] : null;
                $d_lng = isset($param['d_lng']) ? $param['d_lng'] : null;
                $d_lat = isset($param['d_lat']) ? $param['d_lat'] : null;
                $estimate_distance = isset($param['estimate_distance']) ? $param['estimate_distance'] : "";
                $a_lat = isset($param['a_lat']) ? $param['a_lat'] : null;
                $a_lng = isset($param['a_lng']) ? $param['a_lng'] : null;
                $a_address = isset($param['a_address']) ? $param['a_address'] : "";
                $d_address = isset($param['d_address']) ? $param['d_address'] : null;
                $a_address = GeoLocationAlgorithm::getInstance()->simplifyAddress($a_address);
                $d_address = GeoLocationAlgorithm::getInstance()->simplifyAddress($d_address);
                $card_token = isset($param['card_token']) ? $param['card_token'] : null;
                $passenger_names = isset($param['passenger_names']) ? $param['passenger_names'] : "";
                $passenger_count = isset($param['passenger_count']) ? $param['passenger_count'] : 1;
                $bags_count = isset($param['bag_count']) ? $param['bag_count'] : 0;
                $coupon = isset($param['coupon']) ? $param['coupon'] : "";
                $a_airline = isset($param['a_airline']) ? $param['a_airline'] : ["icao" => "", "name" => ""];
                $d_airline = isset($param['d_airline']) ? $param['d_airline'] : ["icao" => "", "name" => ""];
                $a_flight = isset($param['a_flight']) ? $param['a_flight'] : "";
                $d_flight = isset($param['d_flight']) ? $param['d_flight'] : "";
                $d_is_airport = isset($param['d_is_airport']) ? $param['d_is_airport'] : null;
                $a_is_airport = isset($param['a_is_airport']) ? $param['a_is_airport'] : null;
                if (is_null($type) || is_null($offer_id) ||
                    is_null($car_id) || is_null($estimate_duration) ||
                    is_null($d_lng) || is_null($d_lat) || is_null($d_address) ||
                    is_null($cost) || is_null($appointed_time) || is_null($card_token)
                ) {
                    throw new \Exception(ErrorCode::errorMissingParam());
                }

                $realTime = isset($param['real_time']) ? $param['real_time'] : null;

                if (!is_numeric($unit) ||
                    ($unit != CompanySetting::UNIT_MI &&
                        $unit != CompanySetting::UNIT_KM)
                ) {
                    throw new \Exception(ErrorCode::errorParam('unit'));
                }

                if (!is_numeric($bags_count) || $bags_count < 0) {
                    throw new \Exception(ErrorCode::errorParam('bags count'));
                }
                if (!is_numeric($passenger_count) || $passenger_count < 0) {
                    throw new \Exception(ErrorCode::errorParam('passenger count'));
                }

                if (empty($card_token)) {
                    throw new \Exception(ErrorCode::errorParam('card token'));
                } else {
                    $cardInfo = PaymentAlgorithm::getPayment()->checkCreditCardTokenAvailability($card_token, $customer_id, $company_id);
                    if (is_null($cardInfo)) {
                        throw new \Exception(ErrorCode::errorPayFailedWith('card_token'));
                    }
                }

                if (empty($type) || ($type != 1 && $type != 2)) {
                    throw new \Exception(ErrorCode::errorParam('type'));
                }
                if (empty($appointed_time) || !is_numeric($appointed_time) || $appointed_time < time()) {
                    throw new \Exception(ErrorCode::errorOfferUseAppointedTime());
                }
//                if (!GeoLocationAlgorithm::getInstance()->checkAddress($d_address, isset($param['d_is_airport']))) {
//                    throw new \Exception(ErrorCode::errorMissingParam('d_address'));
//                }

                if (is_null($d_is_airport)) {
                    $d_is_airport = isset($d_address['isAirport']) ? ($d_address['isAirport'] ? 1 : 0) : null;
                    if (is_null($d_is_airport)) {
                        throw new \Exception(ErrorCode::errorMissingParam('d_address'));
                    }
                    //GeoLocationAlgorithm::getInstance()->checkAirport($d_address['place_id']) ? 1 : 0;
                } else {
                    if (!is_numeric($d_is_airport) || ($d_is_airport != Offer::IS_AIRPORT && $d_is_airport != Offer::NOT_AIRPORT)) {
                        throw new \Exception(ErrorCode::errorParam("d_is_airport"));
                    }
                }


                if (!is_numeric($d_lng) || $d_lng < -180 || $d_lng > 180) {
                    throw new \Exception(ErrorCode::errorParam('d_lng'));
                }
                if (!is_numeric($d_lat) || $d_lat < -90 || $d_lat > 90) {
                    throw new \Exception(ErrorCode::errorParam('d_lat'));
                }
                if ($type == Booking::CHECK_TYPE_DISTANCE) {
//                    if (!GeoLocationAlgorithm::getInstance()
//                        ->checkAddress($a_address, isset($param['a_is_airport']))
//                    ) {
//                        throw new \Exception(ErrorCode::errorMissingParam('a_address'));
//                    }
                    if (is_null($a_is_airport)) {
                        $a_is_airport = isset($a_address['isAirport']) ? ($a_address['isAirport'] ? 1 : 0) : null;
                        if (is_null($a_is_airport)) {
                            throw new \Exception(ErrorCode::errorMissingParam('a_address'));
                        }
                        //GeoLocationAlgorithm::getInstance()->checkAirport($a_address['place_id']) ? 1 : 0;
                    } else {
                        if (!is_numeric($a_is_airport) || ($a_is_airport != Offer::IS_AIRPORT && $a_is_airport != Offer::NOT_AIRPORT)) {
                            throw new \Exception(ErrorCode::errorParam("a_is_airport"));
                        }
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

                    $offer = $this->offerP2PSearch(
                        $company_id, $unit,
                        $d_lat, $d_lng, $a_lat, $a_lng,
                        $estimate_distance,
                        $appointed_time, $offer_id
                    );
                } elseif ($type == Booking::CHECK_TYPE_HOURLY) {
                    $a_is_airport = 0;
                    $offer = $this->offerHourlySearch($company_id, $unit, $d_lat, $d_lng,
                        $estimate_duration, $appointed_time, $offer_id);
                } else {
                    throw new \Exception(ErrorCode::errorParam("unknown offer type"));
                }
                $temp_a_lng = !empty($a_lng) ? $a_lng : $d_lng;
                $temp_a_lat = !empty($a_lat) ? $a_lat : $d_lat;
                if (empty($offer)) {
                    throw new \Exception(ErrorCode::errorOfferUse());
                }
                //æ£€æŸ¥è½¦è¾†/å¸æœº/offeræ˜¯å¦å¯ç”¨
                if (
                    time() + $offer->delay_time * Constants::MINUTE > $appointed_time ||
                    !$this->checkOfferSpecifiedTimeAvailable($offer_id,
                        $appointed_time, $estimate_duration)
                ) {
                    throw new \Exception(ErrorCode::errorOfferUseAppointedTime());
                }

                $trip_cost = PaymentMethod::offerPriceSettlement($offer->cost_min,
                    $offer->calc_method, $estimate_duration, $estimate_distance, $offer->prices, $unit, $offer->unit,
                    $d_is_airport,
                    isset($offer->d_port_price) ? $offer->d_port_price : 0,
                    $a_is_airport,
                    isset($offer->a_port_price) ? $offer->a_port_price : 0
                );
                $post_options = $param['options'];

                $option_date = array();

                $this->getOfferOptions($offer);
                $option_cost = 0.00;

                foreach ($post_options as $post_option) {
                    foreach ($offer->options as $option) {
                        if ($option->type == 'GROUP') {
                            foreach ($option->group as $item) {
                                if ($post_option['option_id'] == $item->option_id) {
                                    $count = $post_option['count'];
                                    if ($count > $item->add_max) {
                                        throw new \Exception(ErrorCode::errorParam($item->name . " exceeded the maximum number of available"));
                                    }
                                    $option_cost += $item->price * $post_option['count'];
                                    $tempOption = ["option_name" => $item->name, "price" => $item->price, "count" => $count];
                                    array_push($option_date, $tempOption);
                                }

                            }
                        } else {
                            if ($post_option['option_id'] == $option->option_id) {
                                $count = $post_option['count'];
                                if ($count > $option->add_max) {
                                    throw new \Exception(ErrorCode::errorParam($option->name . " exceeded the maximum number of available"));
                                }
                                $option_cost += $option->price * $post_option['count'];
                                $tempOption = ["option_name" => $option->name, "price" => $option->price, "count" => $count];
                                array_push($option_date, $tempOption);
                            }
                        }
                    }
                }

                $base_cost = $trip_cost;
                $trip_cost = $trip_cost + $option_cost;
                $tva_cost = round(($trip_cost) * (1 + $offer->tva / 100), 2);
                $couponOff = 0;

                $couponCode = null;

                if (!empty($coupon)) {
                    if ($company_id != $offer->company_id) {
                        $coupon = "";
                        $couponOff = 0;
                    } else {
                        $payInfo = CompanyPayMethod::where("company_id", $company_id)
                            ->where("active", CompanyPayMethod::ACTIVE)
                            ->first();
                        //$couponCode = PaymentAlgorithm::getPayment()->getCouponInfo($payInfo, $coupon);
                        $couponCode = Coupon::where([['code', $coupon], ['company_id', $company_id]])->first();
                        if (empty($couponCode)) {
                            $coupon = "";
                            $couponOff = 0;
                        } else {
                            /*$couponOff = ($couponCode->amount_off != 0) ?
                                $couponCode->amount_off :
                                ($couponCode->percent_off != 0 ?
                                    round($trip_cost * $couponCode->percent_off / 100, 2) :
                                    0);*/
                            $discount_type = $couponCode->discount_type;
                            if($discount_type == 1) {
                                $couponOff = $tva_cost * $couponCode->discount_amount / 100;
                            }
                            else {
                                $couponOff = $couponCode->discount_amount;
                            }

                        }
                    }
                } else {
                    $coupon = '';
                    $couponOff = 0;
                }

                if ($tva_cost > 0 && $tva_cost < 1) {
                    $tva_cost = 1;
                }
                if ($tva_cost != $cost) {
                    throw new \Exception(ErrorCode::errorParam("offer price was changed" . $tva_cost));
                }

                $car = $this->getCars($car_id, $offer_id, $company_id, $token);
                if (empty($car)) {
                    throw new \Exception(ErrorCode::errorOfferUseCar());
                }

                if (empty($driver_id)) {
                    $drivers = $this->getOfferDriver($offer_id, $car_id, $token);
                    foreach ($drivers as $dri) {
                        if (
                            $this->bookingMatch($car->pre_time, 'driver', $dri->driver_id, $appointed_time, $estimate_duration, $d_lat, $d_lng, $temp_a_lat, $temp_a_lng) &&
                            $this->checkDriverSpecifiedTimeAvailable($dri->driver_id, $appointed_time, $estimate_duration)
                        ) {
                            $driver = $dri;
                            break;
                        }
                    }
                    if (empty($driver)) {
                        throw new \Exception(ErrorCode::errorDriverUseAppointedTime());
                    }
                } else {
                    $driver = $this->getDriver($driver_id, $offer_id, $car_id, $token);
                    if (empty($driver)) {
                        throw new \Exception(ErrorCode::errorOfferUseDriver());
                    }
                }
                $customer = Customer::leftjoin("users", "customers.user_id", "=", "users.id")
                    ->where("customers.id", $customer_id)
                    ->select(
                        "users.first_name",
                        "users.last_name",
                        "users.email",
                        "users.mobile",
                        "users.gender",
                        DB::raw(UrlSpell::getUrlSpell()->
                            getSpellAvatarInDB('users.updated_at', 'users.avatar_url', 'customers.id', $token, UrlSpell::companyCustomerType) . " as avatar_url")
                    )->first();

                $timeInfo = "";

                if(is_null($a_lat) || is_null($a_lng))
                {
                    $timeInfo = GeoLocationAlgorithm::getInstance()
                    ->getLocationTime($d_lat,$d_lng,$appointed_time);
                }else
                {
                    $timeInfo = GeoLocationAlgorithm::getInstance()
                    ->getLocationTime($a_lat,$a_lng,$appointed_time);
                }

                $timezone = isset($timeInfo->timeZoneId)?$timeInfo->timeZoneId:"UTC";
                
                $myDateTime = new \DateTime($realTime, new \DateTimeZone($timezone));

                $pickup_dateTime = new \DateTime($realTime, new \DateTimeZone($timezone));

               $booking = Booking::create([
                    "company_id" => $company_id,
                    "exe_com_id" => $offer->company_id,
                    "car_id" => $car_id,
                    "driver_id" => $driver->driver_id,
                    "type" => $type,
                    "appointed_at" => $myDateTime->setTimeZone(new \DateTimeZone("UTC")),
                    "appointed_at_pickup" => $pickup_dateTime,
                    "d_lat" => $d_lat,
                    "d_lng" => $d_lng,
                    "estimate_time" => $estimate_duration,
                    "unit" => $unit,
                    "estimate_distance" => $estimate_distance,
                    "a_lat" => $a_lat,
                    "a_lng" => $a_lng,
                    "offer_id" => $offer_id,
                    "customer_id" => $user->customer_id,
                    "passenger_names" => $passenger_names,
                    "passenger_count" => $passenger_count,
                    "bags_count" => $bags_count,
                    "base_cost" => round($base_cost * (1 + $offer->tva / 100), 2),
                    "total_cost" => $tva_cost - $couponOff,
                    "option_cost" => round($option_cost * (1 + $offer->tva / 100), 2),
                    "message" => $note,
                    "d_address" => $d_address,
                    "d_is_airport" => $d_is_airport,
                    "a_address" => $a_address,
                    "a_is_airport" => $a_is_airport,
                    "tva" => $offer->tva,
                    "card_token" => $card_token,
                    "coupon" => $coupon,
                    "coupon_off" => $couponOff,
                    "driver_data" => json_encode($driver),
                    "customer_data" => json_encode($customer),
                    "car_data" => json_encode($car),
                    "option_data" => json_encode($option_date),
                    "offer_data" => json_encode($offer),
                    "card_data" => json_encode($cardInfo)
                ]);


                if (
                    !$this->bookingMatch($car->pre_time, 'car', $car_id, $appointed_time, $estimate_duration, $d_lat, $d_lng, $temp_a_lat, $temp_a_lng, $booking->id) ||
                    !$this->checkCarSpecifiedTimeAvailable($car_id, $appointed_time, $estimate_duration)
                ) {
                    throw new \Exception(ErrorCode::errorCarUseAppointedTime());
                }
                if (
                    !$this->bookingMatch($car->pre_time, 'driver', $driver->driver_id, $appointed_time, $estimate_duration,
                        $d_lat, $d_lng, $temp_a_lat, $temp_a_lng, $booking->id) ||
                    !$this->checkDriverSpecifiedTimeAvailable($driver->driver_id, $appointed_time, $estimate_duration)
                ) {
                    throw new \Exception(ErrorCode::errorDriverUseAppointedTime());
                }
                $this->addDriverAndCarEventByBooking($booking->id, $driver, $car,
                    $d_address, $a_address, $appointed_time, $car->pre_time,
                    $estimate_duration, $customer_id, $booking->exe_com_id, $booking->total_cost, $type);
                Order::create([
                    'booking_id' => $booking->id,
                    'trip_state' => Order::TRIP_STATE_WAIT_TO_DEPARTURE,
                    'order_state' => Order::ORDER_STATE_BOOKING
                ]);
                if (!is_array($d_airline)) {
                    $d_airline = [
                        "icao" => $d_airline,
                        "name" => $d_airline
                    ];
                } else {
                    if (!isset($d_airline['icao']) || is_null($d_airline['icao'])) {
                        $d_airline['icao'] = "";
                    }
                    if (!isset($d_airline['name']) || is_null($d_airline['name'])) {
                        $d_airline['name'] = "";
                    }
                }
                if (!is_array($a_airline)) {
                    $a_airline = [
                        "icao" => $a_airline,
                        "name" => $a_airline
                    ];
                } else {
                    if (!isset($a_airline['icao']) || is_null($a_airline['icao'])) {
                        $a_airline['icao'] = "";
                    }
                    if (!isset($a_airline['name']) || is_null($a_airline['name'])) {
                        $a_airline['name'] = "";
                    }
                }


                $airline = BookingAirline::create([
                    "booking_id" => $booking->id,
                    "d_airline_code" => $d_airline['icao'],
                    "d_airline" => $d_airline['name'],
                    "a_airline_code" => $a_airline['icao'],
                    "a_airline" => $a_airline['name'],
                    "d_flight" => $d_flight,
                    "a_flight" => $a_flight
                ]);
                try {
                    PaymentAlgorithm::getPayment()->
                    bookingCharge(
                        $trip_cost,
                        $offer->tva,
                        $booking->id,
                        $customer_id,
                        $card_token,
                        $company_id,
                        $company_id != $offer->company_id,
                        $coupon,
                        $couponOff
                    );
                } catch (\Exception $ex) {
                    Log::error($ex);
                    throw $ex;
                }
                //ä½¿ç”¨æŽ’åº
                RunningError::recordRunningError(RunningError::STATE_SUCCESS,
                    RunningError::TYPE_BOOKING,
                    'booking ' . $booking->id . ' has pay success');
                $credit_card = CreditCard::where([
                    ['card_token', $card_token],
                    ['owner_id', $customer_id],
                    ['type', CreditCard::TYPE_CUSTOMER]
                ])->first();
                if (!empty($credit_card)) {
                    DB::table('credit_cards')
                        ->where([
                            ['owner_id', $customer_id],
                            ['type', CreditCard::TYPE_CUSTOMER],
                        ])
                        ->update(['last_use' => 0]);
                    $credit_card->last_use = 1;
                    $credit_card->save();
                }
                RunningError::recordRunningError(RunningError::STATE_SUCCESS,
                    RunningError::TYPE_BOOKING,
                    'booking ' . $booking->id . ' has change card success');
//                $booking->transaction_sn1 = $payment->id;
//                $booking->payed = 1;
//                $booking->save();
//
                $job = new PushBookingSuccessJob($customer_id, $driver->driver_id);
                dispatch($job);
                RunningError::recordRunningError(RunningError::STATE_SUCCESS,
                    RunningError::TYPE_BOOKING,
                    'booking ' . $booking->id . ' has push success');

                $booking->appointed_at = $appointed_time;
                $booking->driver = $driver;
                $booking->car = $car;
                $booking->airline = $airline;

                //Check and add one time use per customer coupon
                if(!is_null($couponCode)) {
                    if($couponCode->is_onetime == 1) {

                        $user_id = Customer::where('id', $user->customer_id)->value('user_id');
                    
                        $onetime_coupon_history = new Onetime_couponHistory;
                        $onetime_coupon_history->coupon_code = $couponCode->code;
                        $onetime_coupon_history->user_id = $user_id;
                        $onetime_coupon_history->company_id = $company_id;
                        $onetime_coupon_history->used_date = $myDateTime->setTimeZone(new \DateTimeZone("UTC"));

                        $onetime_coupon_history->save();
                        
                    }
                    
                }

                return $booking;
            });
            //MARK ç»Ÿè®¡
            dispatch(new BookingCreateStatisticJob($booking->id));

            // å‘é€é¢„å®šä¿¡æ¯ç»™customer
            dispatch(new SendEmailCustomerBookingJob($booking->id));

            // AN bookingåˆ™å‘é€é‚®ä»¶ç»™å…¬å¸Aå’ŒBã€‚åŽŸå•ä¸”ä¹˜å®¢ç«¯é¢„å®šï¼Œå‘é€é‚®ä»¶ç»™æœ¬å…¬å¸admin
            if ($booking->company_id != $booking->exe_com_id) {
                dispatch(new SendEmailAffiliateBookingJob($booking->id));
            } else {
                dispatch(new SendEmailAdminBookingJob($booking->id));
            }

            return ErrorCode::success($booking);

        } catch (\Exception $ex) {
            return $ex->getMessage();
        }
    }

    private function getOfferDriver($offer_id, $car_id, $token)
    {
        $drivers = OfferDriverCar::leftjoin('drivers', 'offer_driver_cars.driver_id', '=', 'drivers.id')
            ->leftjoin('users', 'drivers.user_id', '=', 'users.id')
            ->where('offer_driver_cars.offer_id', $offer_id)
            ->where('offer_driver_cars.car_id', $car_id)
            ->select('offer_driver_cars.driver_id',
                'drivers.license_number',
                'drivers.hidden_last',
                'users.first_name',
                'users.last_name', 'users.gender',
                'users.mobile',
                'users.email', "users.lang",
                DB::raw(UrlSpell::getUrlSpell()->getSpellAvatarInDB('users.updated_at', 'users.avatar_url', 'offer_driver_cars.driver_id', $token, UrlSpell::companyDriverType) . " as avatar_url"))
            ->get();
        return $drivers;
    }


    private function addDriverAndCarEventByBooking($booking_id, $driver, $car, $d_address, $a_address,
                                                   $appointed_at, $pre_time,
                                                   $duration, $customer_id,
                                                   $company_id, $cost, $type)
    {
        $user = Customer::where('customers.id', $customer_id)
            ->leftjoin('users', 'users.id', '=', 'customers.user_id')
            ->select('users.first_name', 'users.last_name')
            ->first();

        if ($duration < 60) {
            $carTime = $driverTime = round($duration, 1) . " minutes";
        } else {
            $time = round($duration / 60, 1);
            $carTime = $time . (strtolower($car->lang) == 'fr' ? " heures" : " hours");
            $driverTime = $time . (strtolower($driver->lang) == 'fr' ? " heures" : " hours");
        }
//        if ($type == Booking::P2P) {
//            $carContent = $driver->first_name . ' take ' . $user->first_name . 'Â· from ' . $d_address . ' to ' . $a_address;
//            $driverContent = 'take ' . $user->first_name . 'Â·' . $user->last_name . ' from ' . $d_address . ' to ' . $a_address;
//        } elseif ($type == Booking::HOURLY) {
//            $carContent = $driver->first_name . ' take ' . $user->first_name . 'Â· from ' . $d_address . ' for ' . $hours . 'hours';
//            $driverContent = 'take ' . $user->first_name . 'Â·' . $user->last_name . ' on ' . $d_address . ' for ' . $hours . 'hours';
//        } elseif ($type == Booking::CUSTOM) {
//            $carContent = $driver->first_name . ' take ' . $user->first_name . 'Â· from ' . $d_address . ' for ' . $hours . 'hours';
//            $driverContent = 'take ' . $user->first_name . 'Â·' . $user->last_name . ' on ' . $d_address . ' for ' . $hours . 'hours';
//        } else {
//            throw new \Exception('Tommy Lee code bug');
//        }


        \app('translator')->setLocale($car->lang);
        $carContent = Lang::get('events.carEvent', [
            "driverName" => $driver->first_name . " " . $driver->last_name,
            "clientName" => $user->first_name . " " . $user->last_name,
            "time" => $carTime
        ]);
        \app('translator')->setLocale($driver->lang);

        $driverContent = Lang::get('events.driverEvents', [
            "clientName" => $user->first_name . " " . $user->last_name,
            "time" => $driverTime
        ]);

        $startTime = $appointed_at;
        $endTime = $appointed_at + $duration * 60;

        $driverCalendar
            = Calendar::where('owner_id', $driver->driver_id)
            ->where('type', Calendar::DRIVER_TYPE)
            ->first();
//        æžé™å®¹é”™
        $events = $this->matchEvents($driverCalendar, $startTime, $endTime, false);
        if (count($events) != 0) {
            throw new \Exception(ErrorCode::errorAddEventTimeHasBeenUsed('driver'));
        }
        CalendarEvent::create([
            "re_owner_id" => $driverCalendar->owner_id,
            're_type' => $driverCalendar->type,
            "calendar_id" => $driverCalendar->id,
            "re_company_id" => $company_id,
            'content' => $driverContent,
            'start_time' => MethodAlgorithm::formatTimestampToDate($startTime),
            'end_time' => MethodAlgorithm::formatTimestampToDate($endTime),
            "creator_id" => $booking_id,
            'creator_type' => CalendarEvent::CREATOR_TYPE_BOOKING
        ]);

        $carCalendar
            = Calendar::where('owner_id', $car->car_id)
            ->where('type', Calendar::CAR_TYPE)
            ->first();
        //æžé™å®¹é”™
        $events = $this->matchEvents($carCalendar, $startTime, $endTime, false);
        if (count($events) != 0) {
//            echo $events;
            throw new \Exception(ErrorCode::errorAddEventTimeHasBeenUsed('car'));
        }
        CalendarEvent::create([
            "re_owner_id" => $carCalendar->owner_id,
            're_type' => $carCalendar->type,
            "calendar_id" => $carCalendar->id,
            "re_company_id" => $company_id,
            'content' => $carContent,
            'start_time' => MethodAlgorithm::formatTimestampToDate($startTime),
            'end_time' => MethodAlgorithm::formatTimestampToDate($endTime),
            "creator_id" => $booking_id,
            'creator_type' => CalendarEvent::CREATOR_TYPE_BOOKING
        ]);
    }

    public function addCustomQuote($company_id, $customer_id, $param, $customDetermine, $token)
    {
        $booking = DB::transaction(function () use ($company_id, $customer_id, $param, $token, $customDetermine) {
            $d_lat = isset($param['d_lat']) ? $param['d_lat'] : null;
            $d_lng = isset($param['d_lng']) ? $param['d_lng'] : null;
            $d_address = isset($param['d_address']) ? $param['d_address'] : null;
            $appointed_time = isset($param['appointed_time']) ? $param['appointed_time'] : null;
            $car_id = isset($param['car_id']) ? $param['car_id'] : null;
            $driver_id = isset($param['driver_id']) ? $param['driver_id'] : "";
            $unit = isset($param['unit']) ? $param['unit'] : CompanySetting::UNIT_MI;
            $estimate_duration = isset($param['estimate_duration']) ? $param['estimate_duration'] : null;
            $tva = isset($param['tva']) ? $param['tva'] : null;
            $note = isset($param['note']) ? $param['note'] : "";
            $cost = isset($param['cost']) ? $param['cost'] : null;
            $passenger_names = isset($param['passenger_names']) ? $param['passenger_names'] : "";
            $passenger_count = isset($param['passenger_count']) ? $param['passenger_count'] : 1;
            $bags_count = isset($param['bag_count']) ? $param['bag_count'] : 0;
            $card_token = isset($param['card_token']) ? $param['card_token'] : null;
            $d_airline = isset($param['d_airline']) ? $param['d_airline'] : ["name" => "", "icao" => ""];
            $d_flight = isset($param['d_flight']) ? $param['d_flight'] : "";
            $d_is_airport = isset($param['d_is_airport']) ? $param['d_is_airport'] : 0;
            if (is_null($driver_id) || is_null($tva) ||
                is_null($car_id) || is_null($estimate_duration) ||
                is_null($d_lng) || is_null($d_lat) || is_null($d_address) ||
                is_null($cost) || is_null($appointed_time) || is_null($card_token)
            ) {
                throw new \Exception(ErrorCode::errorMissingParam());
            }
            if (!is_numeric($unit) ||
                ($unit != CompanySetting::UNIT_MI &&
                    $unit != CompanySetting::UNIT_KM)
            ) {
                throw new \Exception(ErrorCode::errorParam("unit"));
            }
            if (!is_numeric($cost) || $cost < 0) {
                throw new \Exception(ErrorCode::errorParam('cost'));
            } else {
                if ($cost > 0 && $cost < 1) {
                    throw new \Exception(ErrorCode::errorLessAmountFault());
                }
            }

            if (!is_numeric($bags_count) || $bags_count < 0) {
                throw new \Exception(ErrorCode::errorParam('bags count'));
            }
            if (!is_numeric($passenger_count) || $passenger_count < 0) {
                throw new \Exception(ErrorCode::errorParam('passenger count'));
            }
            if (empty($appointed_time) || !is_numeric($appointed_time) || $appointed_time < time()) {
                throw new \Exception(ErrorCode::errorOfferUseAppointedTime());
            }
            if (!GeoLocationAlgorithm::getInstance()->checkAddress($d_address, $param['d_is_airport'])) {
                throw new \Exception(ErrorCode::errorMissingParam('d_address'));
            }

            if (is_null($d_is_airport)) {
                $d_is_airport = GeoLocationAlgorithm::getInstance()->checkAirport($d_address['place_id']) ? 1 : 0;
            } else {
                if (!is_numeric($d_is_airport) ||
                    ($d_is_airport != 0 &&
                        $d_is_airport != 1)
                ) {
                    throw new \Exception(ErrorCode::errorParam('d_is_airport'));
                }
            }

            if (!is_numeric($d_lng) || $d_lng < -180 || $d_lng > 180) {
                throw new \Exception(ErrorCode::errorParam('d_lng'));
            }
            if (!is_numeric($d_lat) || $d_lat < -90 || $d_lat > 90) {
                throw new \Exception(ErrorCode::errorParam('d_lat'));
            }

            if (!is_numeric($tva) || $tva < 0) {
                throw new \Exception(ErrorCode::errorParam('tva'));
            }

            if (!is_numeric($estimate_duration) || $estimate_duration < 0) {
                throw new \Exception(ErrorCode::errorParam('estimate_duration'));
            }
            if (empty($card_token)) {
                throw new \Exception(ErrorCode::errorParam('card_token'));
            } else {
                $cardInfo = PaymentAlgorithm::getPayment()->checkCreditCardTokenAvailability($card_token, $customer_id, $company_id);
                if (is_null($cardInfo)) {
                    throw new \Exception(ErrorCode::errorParam('card_token'));
                }
            }
            if (!is_numeric($driver_id)) {
                throw new \Exception(ErrorCode::errorParam('driver_id'));
            } else {
                $driver = Driver::leftjoin('users', 'drivers.user_id', '=', 'users.id')
                    ->where('drivers.id', $driver_id)
                    ->select('drivers.id as driver_id',
                        'drivers.delay_time',
                        'drivers.license_number',
                        'drivers.hidden_last', "users.lang",
                        'users.first_name',
                        'users.last_name', 'users.avatar_url',
                        'users.gender', 'users.mobile', "users.email",
                        DB::raw(UrlSpell::getUrlSpell()->getSpellAvatarInDB('users.updated_at',
                                'users.avatar_url', 'drivers.id', $token,
                                UrlSpell::companyDriverType) . " as avatar_url"))
                    ->first();
                if (empty($driver)) {
                    throw new \Exception(ErrorCode::errorNotExist('driver'));
                }
            }


            if (!is_numeric($car_id)) {
                throw new \Exception(ErrorCode::errorParam('car_id'));
            } else {
                $car = Car::leftjoin('car_models', 'car_models.id', '=', 'cars.car_model_id')
                    ->leftjoin('car_brands', 'car_brands.id', '=', 'car_models.car_brand_id')
                    ->leftjoin('company_settings', "company_settings.company_id", "=", "cars.company_id")
                    ->where('cars.id', $car_id)
                    ->select('cars.id as car_id', 'car_models.name as model',
                        'cars.bags_max', 'cars.seats_max', 'car_models.car_category_id', 'cars.pre_time',
                        'cars.license_plate as license_plate', 'cars.year as year', 'cars.color as color',
                        "company_settings.lang",
                        UrlSpell::getUrlSpell()->getCarsImgInDB($company_id, $token),
                        'car_brands.name as brand')
                    ->first();
                if (empty($car)) {
                    throw new \Exception(ErrorCode::errorNotExist('car'));
                }
            }
            $offer = new Offer();
            $offer->cost_min = $cost;
            $offer->price = 0;
            $offer->calc_method = 0;
            $offer->tva = $tva;
            $customer = Customer::leftjoin("users", "customers.user_id", "=", "users.id")
                ->where("customers.id", $customer_id)
                ->select(
                    "users.first_name",
                    "users.last_name",
                    "users.email",
                    "users.mobile",
                    "users.gender",
                    DB::raw(UrlSpell::getUrlSpell()->
                        getSpellAvatarInDB('users.updated_at', 'users.avatar_url', 'customers.id', $token, UrlSpell::companyCustomerType) . " as avatar_url")
                )->first();

            $timeInfo = "";

            if(is_null($a_lat) || is_null($a_lng))
            {
                $timeInfo = GeoLocationAlgorithm::getInstance()
                ->getLocationTime($d_lat,$d_lng,$appointed_time);
            }else
            {
                $timeInfo = GeoLocationAlgorithm::getInstance()
                ->getLocationTime($a_lat,$a_lng,$appointed_time);
            }

            $timezone = isset($timeInfo->timeZoneId)?$timeInfo->timeZoneId:"UTC";
            
            $pickup_dateTime = new \DateTime($realTime, new \DateTimeZone($timezone));


            $booking = Booking::create([
                "company_id" => $company_id,
                "exe_com_id" => $company_id,
                "car_id" => $car_id,
                "driver_id" => $driver_id,
                "type" => Booking::CHECK_TYPE_CUSTOM,
                "appointed_at" => MethodAlgorithm::formatTimestampToDate($appointed_time),
                "appointed_at_pickup" => $pickup_dateTime,
                "d_lat" => $d_lat,
                "d_lng" => $d_lng,
                "estimate_time" => $estimate_duration,
                "unit" => $unit,
                "customer_id" => $customer_id,
                "total_cost" => $cost,
                "base_cost" => $cost,
                "message" => $note,
                "d_address" => $d_address,
                "d_is_airport" => $d_is_airport,
                "tva" => $tva,
                "passenger_names" => $passenger_names,
                "passenger_count" => $passenger_count,
                "bags_count" => $bags_count,
                "option_data" => "[]",
                "offer_data" => json_encode($offer),
                "card_token" => $card_token,
                "driver_data" => json_encode($driver),
                "customer_data" => json_encode($customer),
                "car_data" => json_encode($car),
                "custom_auth_code" => str_random(32),
                "card_data" => json_encode($cardInfo)
            ]);

            if (
                !$this->bookingMatch($car->pre_time, 'car', $car_id, $appointed_time, $estimate_duration
                    , $d_lat, $d_lng, $d_lat, $d_lng, $booking->id) ||
                !$this->checkCarSpecifiedTimeAvailable($car_id, $appointed_time, $estimate_duration)
            ) {
                throw new \Exception(ErrorCode::errorCarUseAppointedTime());
            }
            if (
                !$this->bookingMatch($car->pre_time, 'driver', $driver->driver_id, $appointed_time, $estimate_duration,
                    $d_lat, $d_lng, $d_lat, $d_lng, $booking->id) ||
                !$this->checkDriverSpecifiedTimeAvailable($driver->driver_id, $appointed_time, $estimate_duration)
            ) {
                throw new \Exception(ErrorCode::errorDriverUseAppointedTime());
            }


            $this->addDriverAndCarEventByBooking($booking->id, $driver, $car,
                $d_address, "", $appointed_time, $car->pre_time,
                $estimate_duration, $customer_id, $booking->exe_com_id, $booking->total_cost, Booking::CHECK_TYPE_CUSTOM);

            if (!is_array($d_airline)) {
                $d_airline = [
                    "icao" => "",
                    "name" => $d_airline
                ];
            } else {
                if (!isset($d_airline['icao']) || is_null($d_airline['icao'])) {
                    $d_airline['icao'] = "";
                }
                if (!isset($d_airline['name']) || is_null($d_airline['name'])) {
                    $d_airline['name'] = "";
                }
            }
            BookingAirline::create([
                "booking_id" => $booking->id,
                "d_airline" => $d_airline['name'],
                "d_airline_code" => $d_airline['icao'],
//                "a_airline" => "",
                "d_flight" => $d_flight,
//                "a_flight" => ""
            ]);

            if ($customDetermine == 1) {
                //send customer determine email
                $sendEmail = (new SendEmailCustomQuoteDetermineJob($booking));
                dispatch($sendEmail);

                Order::create([
                    'booking_id' => $booking->id,
                    'trip_state' => Order::TRIP_STATE_WAIT_TO_DEPARTURE,
                    'order_state' => Order::ORDER_STATE_WAIT_DETERMINE
                ]);
            } else {
                PaymentAlgorithm::getPayment()->bookingCharge(
                    $cost, 0, $booking->id, $customer_id, $card_token, $company_id, false);
                $job = new PushBookingSuccessJob($customer_id, $driver_id);
                dispatch($job);
                Order::create([
                    'booking_id' => $booking->id,
                    'trip_state' => Order::TRIP_STATE_WAIT_TO_DEPARTURE,
                    'order_state' => Order::ORDER_STATE_BOOKING
                ]);
            }
            //MARK ç»Ÿè®¡
            dispatch(new BookingCreateStatisticJob($booking->id));
            dispatch(new SendEmailCustomerBookingJob($booking->id));
            return $booking;
        });
        return ErrorCode::success($booking);
    }
}