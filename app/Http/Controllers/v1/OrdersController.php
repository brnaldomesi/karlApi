<?php

namespace App\Http\Controllers\v1;

use App\Constants;
use App\ErrorCode;
use App\Jobs\BookingFeedbackJob;
use App\Method\OrderStateAlgorithm;
use App\Model\Bill;
use App\Model\CompanySetting;
use App\PushMsg;
use App\Jobs\PushCustomerJob;
use App\Method\MethodAlgorithm;
use App\Method\PaymentMethod;
use App\Method\UrlSpell;
use App\Model\Booking;
use App\Model\Car;
use App\Model\Company;
use App\Model\Driver;
use App\Model\Feedback;
use App\Jobs\PayTripSettleJob;
use App\Model\Offer;
use App\Model\Order;
use App\Model\Track;
use \Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Input;

class OrdersController extends Controller
{

    public function customerGetOrderState(Request $request)
    {
        $customer_id = $request->user->customer->id;
        $booking_id = Input::get('booking_id', null);
        if (is_null($booking_id)) {
            return ErrorCode::errorMissingParam();
        }
        $result = Booking::where('bookings.customer_id', $customer_id)
            ->where('bookings.id', $booking_id)
            ->leftjoin('booking_transaction_histories as bth', 'bth.booking_id', '=', 'bookings.id')
            ->leftjoin('drivers', 'drivers.id', '=', 'bookings.driver_id')
            ->leftjoin('users', 'drivers.user_id', '=', 'users.id')
            ->leftjoin('orders', 'orders.booking_id', '=', 'bookings.id')
            ->leftjoin('tracks', 'orders.id', '=', 'tracks.order_id')
            ->whereRaw('orders.trip_state is not null')
            ->select(
                'users.first_name as driver_first_name',
                'users.last_name as driver_last_name',
                'users.mobile as driver_mobile',
                'tracks.created_at as last_report_time',
                'tracks.lng as last_report_lng',
                'tracks.lat as last_report_lat',
                'tracks.address as last_address',
                'tracks.trip_cost',
                'bookings.unit',
                'bth.ccy',
                DB::raw('UNIX_TIMESTAMP(bookings.appointed_at) as appointed_at'),
                DB::raw('(UNIX_TIMESTAMP(now())-UNIX_TIMESTAMP(ifnull(orders.start_time,now()))) as last_time'),
                'orders.trip_state',
                'orders.order_state')
            ->orderBy('tracks.pointed_at', 'desc')
            ->first();
        if (empty($result)) {
            return ErrorCode::successEmptyResult('no data for this booking');
        }
        return ErrorCode::success($result);
    }

    public function companyGetFeedback(Request $request)
    {
        $company_id = $request->user->company_id;
        $token = $request->user->token;
        $page = Input::get('page', Constants::PAGE_DEFAULT);
        $per_page = Input::get('per_page', Constants::PER_PAGE_DEFAULT);
        if ($per_page > 100 || $per_page < 1 || !is_numeric($per_page)) {
            $per_page = Constants::PER_PAGE_DEFAULT;
        }
        if ($page < 1 || !is_numeric($page)) {
            $page = Constants::PAGE_DEFAULT;
        }
        $skip = $per_page * ($page - 1);
        $result['total'] = Order::leftjoin('bookings', 'bookings.id', '=', 'orders.booking_id')
            ->where('bookings.company_id', $company_id)
            ->where('orders.trip_state', '>=', Order::TRIP_STATE_WAITING_TO_SETTLE)
            ->count();


        $result['feedbacks'] = Booking::leftjoin('drivers', 'drivers.id', '=', 'bookings.driver_id')
            ->leftjoin('users as du', 'du.id', '=', 'drivers.user_id')
            ->leftjoin('customers', 'customers.id', '=', 'bookings.customer_id')
            ->leftjoin('users as cu', 'cu.id', '=', 'customers.user_id')
            ->leftjoin('orders', 'orders.booking_id', '=', 'bookings.id')
            ->leftjoin('feedbacks', 'feedbacks.order_id', '=', 'orders.id')
            ->select(
                'cu.id AS customer_id',
                'cu.first_name AS customer_first_name',
                'cu.last_name AS customer_last_name',
                'cu.mobile AS customer_mobile',
                DB::raw(UrlSpell::getUrlSpell()
                        ->getSpellAvatarInDB('cu.updated_at', 'cu.avatar_url', 'cu.id',
                            $token, UrlSpell::companyCustomerType) . ' AS customer_avatar'),
                'du.id AS driver_id',
                'du.first_name AS driver_first_name',
                'du.last_name AS driver_last_name',
                'du.mobile AS driver_mobile',
                DB::raw(UrlSpell::getUrlSpell()
                        ->getSpellAvatarInDB('du.updated_at', 'du.avatar_url', 'du.id',
                            $token, UrlSpell::companyDriverType) . " as driver_avatar"),
                'feedbacks.appearance',
                'feedbacks.professionalism',
                'feedbacks.driving_ability',
                'feedbacks.cleanliness',
                'feedbacks.quality',
                'feedbacks.comment'
            )->where('orders.trip_state', '>=', Order::TRIP_STATE_WAITING_TO_SETTLE)
            ->orderBy('feedbacks.created_at', 'desc')
            ->take($per_page)
            ->skip($skip)
            ->get();

        return ErrorCode::success($result);
    }

    public function companyGetOrderState(Request $request)
    {
        $company_id = $request->user->company_id;
        $token = $request->user->token;
        $startTime = Input::get('start_time', null);
        $endTime = Input::get('end_time', '');
        $filter = Input::get('filter', Constants::BOOK_FILTER_ALL);
        if (is_null($startTime) || !is_numeric($startTime)) {
            return ErrorCode::errorParam("start time");
        }

        if (empty($endTime) || !is_numeric($endTime)) {
            $endTime = $startTime + Constants::DAY_SECONDS;
        }


        $orderState = [
            Order::ORDER_STATE_BOOKING,
            Order::ORDER_STATE_RUN
        ];
        $tripState = [
            Order::TRIP_STATE_WAIT_TO_DEPARTURE,
            Order::TRIP_STATE_DRIVE_TO_PICK_UP,
            Order::TRIP_STATE_WAITING_CUSTOMER,
            Order::TRIP_STATE_GO_TO_DROP_OFF
        ];
        $bookings = Booking::leftjoin('orders', 'orders.booking_id', '=', 'bookings.id')
            ->leftjoin('booking_transaction_histories as bth', 'bth.booking_id', '=', 'bookings.id')
            ->leftjoin('customers', 'customers.id', '=', 'bookings.customer_id')
            ->leftjoin("booking_airlines", "bookings.id", "=", "booking_airlines.booking_id")
            ->leftjoin('users', 'users.id', '=', 'customers.user_id')
            ->leftjoin('companies as ec', "ec.id", "=", "bookings.exe_com_id")
            ->leftjoin('companies as oc', "oc.id", "=", "bookings.company_id")
            ->where(function ($query) use ($company_id, $filter) {
                if ($filter == Constants::BOOK_FILTER_OWN) {
                    $query->where('bookings.company_id', $company_id);
                } else if ($filter == Constants::BOOK_FILTER_EXE) {
                    $query->where('bookings.exe_com_id', $company_id);
                } else if ($filter == Constants::BOOK_FILTER_BOOKING_OWN) {
                    $query->where('bookings.exe_com_id', $company_id)
                        ->where('bookings.company_id', $company_id);
                } else if ($filter == Constants::BOOK_FILTER_BOOKING_OTHER) {
                    $query->where(function ($query) use ($company_id) {
                        $query->where('bookings.exe_com_id', $company_id)
                            ->where('bookings.company_id', '!=', $company_id);
                    })
                        ->orWhere(function ($query) use ($company_id) {
                            $query->where('bookings.company_id', $company_id)
                                ->where('bookings.exe_com_id', '!=', $company_id);
                        });
                } else {
                    $query->where('bookings.company_id', $company_id)
                        ->orWhere('bookings.exe_com_id', $company_id);
                }
            })
            ->whereRaw("unix_timestamp(bookings.appointed_at)>={$startTime}")
            ->whereRaw("unix_timestamp(bookings.appointed_at)<{$endTime}")
            ->whereIn('orders.order_state', $orderState)
            ->whereIn('orders.trip_state', $tripState)
            ->select(
                'bookings.company_id',
                'bookings.car_data',
                'bookings.driver_data',
                'bookings.customer_data',
                'bookings.option_data',
                'bookings.offer_data',
                DB::raw('
                    CASE WHEN orders.trip_state > ' . Order::TRIP_STATE_WAITING_TO_SETTLE . ' THEN
                        orders.actual_fee
                    ELSE
                        bookings.total_cost
                    END as total_cost'),
                DB::raw('
                    CASE WHEN orders.actual_fee-bookings.total_cost > 0 THEN
                        orders.actual_fee-bookings.total_cost
                    ELSE
                        0.00
                    END as spreads'),
                DB::raw('UNIX_TIMESTAMP(bookings.appointed_at) as appointed_at'),
                'orders.trip_state',
                'orders.order_state',
                DB::raw("UNIX_TIMESTAMP(IFNULL(orders.departure_time,'0000-00-00 00:00:00')) as departure_time"),
                DB::raw("UNIX_TIMESTAMP(IFNULL(orders.reach_time,'0000-00-00 00:00:00')) as reach_time"),
                DB::raw("UNIX_TIMESTAMP(IFNULL(orders.start_time,'0000-00-00 00:00:00')) as start_time"),
                DB::raw("UNIX_TIMESTAMP(IFNULL(orders.finish_time,'0000-00-00 00:00:00')) as finish_time"),
                DB::raw("UNIX_TIMESTAMP(IFNULL(orders.settle_time,'0000-00-00 00:00:00')) as settle_time"),
                'bookings.id',
                'bookings.d_address',
                'bookings.d_lat',
                'bookings.d_lng',
                'bookings.a_address',
                'bookings.a_lat',
                'bookings.a_lng',
                'bookings.type',
                'bookings.message',
                'bookings.estimate_time',
                'bookings.estimate_distance',
                'bookings.passenger_count',
                'bookings.bags_count',
                'bookings.reject',
                'bookings.passenger_names',
                'bookings.unit',
                'bth.ccy',
                'booking_airlines.a_airline',
                'booking_airlines.d_airline',
                'booking_airlines.a_flight',
                'booking_airlines.d_flight',
                "orders.last_report_time",
                "orders.last_lng AS last_report_lng",
                "orders.last_lat AS last_report_lat",
                "orders.last_distance AS last_distance",
                "orders.last_address AS last_address",
                "orders.last_speed AS last_speed",
                'oc.name as own_company_name',
                'oc.id as own_company_id',
                'oc.phone1 as own_company_phone1',
                'oc.phone2 as own_company_phone2',
                'oc.email as own_company_email',
                DB::raw(UrlSpell::getUrlSpell()->getCompaniesLogoByNameInDB('own_company_logo', 'oc')),
                'ec.name as exe_company_name',
                'ec.id as exe_company_id',
                'ec.phone1 as exe_company_phone1',
                'ec.phone2 as exe_company_phone2',
                'ec.email as exe_company_email',
                DB::raw(UrlSpell::getUrlSpell()->getCompaniesLogoByNameInDB('exe_company_logo', 'ec')),
                DB::raw(UrlSpell::getUrlSpell()->
                    getSpellAvatarInDB('users.updated_at', 'users.avatar_url',
                        'customers.id', $token, UrlSpell::companyCustomerType) . ' as c_avatar_url')
            )
            ->orderBy('bookings.appointed_at', 'asc')
            ->get();

        if (count($bookings) == 0) {
            return ErrorCode::successEmptyResult('no booking on trip');
        }
        foreach ($bookings as $booking) {
            $customer = json_decode($booking->customer_data);
            $booking->c_first_name = $customer->first_name;
            $booking->c_last_name = $customer->last_name;
            $booking->c_gender = $customer->gender;
            $booking->c_mobile = $customer->mobile;
            $booking->c_email = $customer->email;
            $booking->c_avatar = $customer->avatar_url;
        }

        return ErrorCode::success($bookings);
    }


    public function addOrderFeedback()
    {
        $appearance = Input::get("appearance", null);
        $professionalism = Input::get("professionalism", null);
        $driving_ability = Input::get("driving_ability", null);
        $cleanliness = Input::get("cleanliness", null);
        $quality = Input::get("quality", null);
        $comment = Input::get("comment", "");
        $booking_id = Input::get("booking_id", null);

        if (is_null($appearance) || is_null($professionalism) ||
            is_null($driving_ability) || is_null($cleanliness) ||
            is_null($quality) || is_null($booking_id)
        ) {
            return ErrorCode::errorMissingParam();
        }
        if (!is_numeric($appearance) || $appearance < 0 || $appearance > 10) {
            return ErrorCode::errorParam('appearance');
        }

        if (!is_numeric($professionalism) || $professionalism < 0 || $professionalism > 10) {
            return ErrorCode::errorParam('professionalism');
        }

        if (!is_numeric($driving_ability) || $driving_ability < 0 || $driving_ability > 10) {
            return ErrorCode::errorParam('driving_ability');
        }

        if (!is_numeric($cleanliness) || $cleanliness < 0 || $cleanliness > 10) {
            return ErrorCode::errorParam('cleanliness');
        }

        if (!is_numeric($quality) || $quality < 0 || $quality > 10) {
            return ErrorCode::errorParam('quality');
        }
        $order = Order::where('booking_id', $booking_id)->first();
        if (empty($order)) {
            return ErrorCode::errorNotExist('order');
        }
        //订单是否结束判断
        if ($order->order_state < Order::ORDER_STATE_DONE) {
            return ErrorCode::errorOrderFeedBack();
        }
        if ($order->feedbacked) {
            return ErrorCode::errorOrderFeedBackHasAdded();
        }

        $booking = Booking::where('id', $booking_id)
            ->select('driver_id', 'car_id')
            ->first();
        $driver = Driver::where('id', $booking->driver_id)->first();
        if (!empty($driver)) {
            $driver->count_appear = $driver->count_appear + $appearance;
            $driver->count_profess = $driver->count_profess + $professionalism;
            $driver->count_drive = $driver->count_drive + $driving_ability;
            $driver->count_clean = $driver->count_clean + $cleanliness;
            $driver->count_rating = $driver->count_rating + 1;
            $driver->save();
        }
        $car = Car::where('id', $booking->car_id)->first();
        if (!empty($car)) {
            $car->count_quality = $car->count_quality + $quality;
            $car->count_rating = $car->count_rating + 1;
            $car->save();
        }
        $feedback = Feedback::create([
            'order_id' => $order->id,
            'appearance' => $appearance,
            'professionalism' => $professionalism,
            'driving_ability' => $driving_ability,
            'cleanliness' => $cleanliness,
            'quality' => $quality,
            'comment' => $comment
        ]);
        $this->dispatch(new BookingFeedbackJob($order->id));
        unset($feedback->order_id);
        return ErrorCode::success($feedback);
    }


    public function updateDriverLocation(Request $request)
    {
        $driver_id = $request->user->driver->id;
        $booking_id = Input::get('booking_id', null);
        $locations = Input::get('locations', null);
        $locations = json_decode($locations, true);
        //判断booking id 是否为空,是否为数字型
        if (empty($booking_id) || !is_numeric($booking_id)) {
            return ErrorCode::errorParam('booking_id');
        }
        $booking = Booking::where('id', $booking_id)->first();
        //判断booking是否存在
        if (empty($booking)) {
            return ErrorCode::errorNotExist('booking');
        }
        //判断locations 是否转换正确且有值
        if (empty($locations)) {
            return ErrorCode::errorParam('locations');
        }

        $order = Order::leftjoin('bookings', 'bookings.id', '=', 'orders.booking_id')
            ->where([
                ['orders.booking_id', $booking_id],
                ['bookings.driver_id', $driver_id],
            ])
            ->select(
                DB::raw("UNIX_TIMESTAMP(orders.start_time) as start_time"),
                'bookings.offer_data', "bookings.type",
                "bookings.total_cost", "bookings.base_cost",
                "bookings.free_fee", "orders.admin_action",
                "bookings.estimate_time", "bookings.estimate_distance",
                "bookings.d_is_airport",
                "bookings.a_is_airport",
                'bookings.option_cost', 'orders.trip_state', 'orders.id')
            ->first();
        if (empty($order)) {
            return ErrorCode::errorParam("booking_id");
        }
        //订单未开始不接收
        if ($order->trip_state == Order::TRIP_STATE_WAIT_TO_DEPARTURE) {
            return ErrorCode::errorOrderTripHasNotBeenStarted();
        }
        //订单已结束不接收
        if (
            $order->order_state == Order::ORDER_STATE_ADMIN_CANCEL ||
            $order->order_state == Order::ORDER_STATE_SUPER_ADMIN_CANCEL ||
            $order->order_state == Order::ORDER_STATE_PASSENGER_CANCEL ||
            $order->trip_state >= Order::TRIP_STATE_WAITING_DRIVER_DETERMINE
        ) {
            return ErrorCode::errorOrderTripHasBeenFinished();
        }

        try {
            $tracks = DB::transaction(function () use ($order, $locations) {
                $tracks = array();
                $lastPoint = 0;
                $local = null;
                foreach ($locations as $location) {
                    $distance = isset($location['distance']) ? $location['distance'] : null;
                    $lat = isset($location['lat']) ? $location['lat'] : null;
                    $lng = isset($location['lng']) ? $location['lng'] : null;
                    $address = isset($location['address']) ? $location['address'] : null;
                    $unit = isset($location['unit']) ? $location['unit'] : CompanySetting::UNIT_MI;
                    $speed = isset($location['speed']) ? $location['speed'] : null;
                    $pointed_at = isset($location['pointed_at']) ? $location['pointed_at'] : null;
                    if (
                        is_null($lat) ||
                        is_null($speed) ||
                        is_null($distance) ||
                        is_null($lng) ||
                        is_null($address) ||
                        is_null($pointed_at)
                    ) {
                        throw new \Exception(ErrorCode::errorMissingParam());
                    }


                    if (!is_numeric($distance) || $distance < 0) {
                        throw new \Exception(ErrorCode::errorParam('distance'));
                    }
                    if (!is_numeric($pointed_at) || $pointed_at < 0) {
                        throw new \Exception(ErrorCode::errorParam('pointed_at'));
                    }
                    if (!is_numeric($speed) || $speed < 0) {
                        throw new \Exception(ErrorCode::errorParam('speed'));
                    }
                    if (!is_numeric($lat) || $lat > 90 || $lat < -90) {
                        throw new \Exception(ErrorCode::errorParam('lat'));
                    }
                    if (!is_numeric($lng) || $lng > 180 || $lng < -180) {
                        throw new \Exception(ErrorCode::errorParam('lng'));
                    }
                    if (is_null($address)) {
                        throw new \Exception(ErrorCode::errorParam('address'));
                    }


                    $line = $order->trip_state > Order::TRIP_STATE_WAITING_CUSTOMER ? 2 : 1;
                    $offer = json_decode($order->offer_data, true);
                    if ($line == 2) {
                        if ($order->type == Offer::CHECK_TYPE_CUSTOM) {
                            $cost = $order->total_cost;
                        } else {
                            if ($order->admin_action == Order::ADMIN_ACTION_START) {
                                $cost = $order->total_cost;
                            } else {
                                $timeDuration = (time() - $order->start_time) / 60;
                                if ($order->type == Booking::CHECK_TYPE_HOURLY) {
                                    $cost = PaymentMethod::offerPriceSettlement($offer['cost_min'],
                                            $offer['calc_method'],
                                            $timeDuration,
                                            $distance,
                                            isset($offer['price']) ? $offer['price'] : $offer['prices'],
                                            $unit,
                                            isset($offer['unit']) ? $offer['unit'] : CompanySetting::UNIT_MI,
                                            $order->d_is_port,
                                            isset($offer['d_port_price']) ? $offer['d_port_price'] : 0,
                                            is_null($order->a_is_port) ? 0 : $order->a_is_port,
                                            isset($offer['a_port_price']) ? $offer['a_port_price'] : 0
                                        ) * ($offer['tva'] / 100 + 1);
                                    if ($timeDuration <= $order->estimate_time) {
                                        $cost = $order->base_cost;
                                    }
                                } else {
                                    $cost = PaymentMethod::offerPriceSettlement($offer['cost_min'],
                                            $offer['calc_method'],
                                            $timeDuration,
                                            $distance,
                                            $unit,
                                            isset($offer['unit']) ? $offer['unit'] : CompanySetting::UNIT_MI,
                                            isset($offer['price']) ? $offer['price'] : $offer['prices'],
                                            $order->d_is_port,
                                            isset($offer['d_port_price']) ? $offer['d_port_price'] : 0,
                                            is_null($order->a_is_port) ? 0 : $order->a_is_port,
                                            isset($offer['a_port_price']) ? $offer['a_port_price'] : 0
                                        ) * ($offer['tva'] / 100 + 1);
                                    if ($distance <= $order->estimate_distance) {
                                        $cost = $order->base_cost;
                                    }
                                }
                                if ($order->free_fee > 0) {
                                    if ($cost < $order->free_fee) {
                                        $cost = $order->base_cost;
                                    } else {
                                        $cost = ($cost - $order->free_fee) < $order->base_cost ? $order->base_cost : $cost - $order->free_fee;
                                    }
                                } elseif ($order->free_fee == 0) {
//                                $cost = $cost;
                                } else {
                                    if ($cost < $order->base_cost) {
                                        $cost = $order->base_cost;
                                    }
                                }
                                $cost = $order->option_cost + $cost;
                            }

                        }
                    } else {
                        $cost = 0;
                    }
                    if($pointed_at > $lastPoint){
                        $lastPoint = $pointed_at;
                        $local = $location;
                    }
                    $track = Track::create([
                        'order_id' => $order->id,
                        'line' => $line,
                        'lat' => $lat,
                        'lng' => $lng,
                        'unit' => $unit,
                        'distance' => $distance,
                        'address' => $address,
                        'speed' => $speed,
                        'trip_cost' => $cost,
                        'pointed_at' => MethodAlgorithm::formatTimestampToDate($pointed_at)
                    ]);
                    $track->cost = $cost;
                    unset($track->order_id);
                    array_push($tracks, $track);
                }
                if(!is_null($local) && $lastPoint != 0){
                    Order::where("id",$order->id)->update(
                        [
                            "last_report_time"=>$lastPoint,
                            "last_lat"=>$local['lat'],
                            "last_lng"=>$local['lng'],
                            "last_address"=>$local['address'],
                            "last_speed"=>$local['speed'],
                            "last_distance"=>$local['distance']
                        ]
                    );
                }
                return $tracks;
            });
            $result = ["start_time" => $order->start_time, "tracks" => $tracks];
            return ErrorCode::success($result);
        } catch (\Exception $ex) {
            return $ex->getMessage();
        }

    }


    public function updateOrderState(Request $request)
    {
        $driver_id = $request->user->driver->id;
        $booking_id = Input::get('booking_id', null);
        $unit = Input::get('unit', CompanySetting::UNIT_MI);
        $distance = Input::get('distance', null);
        $state = Input::get('state', null);
        $address = Input::get('address', '');
        $lat = Input::get('lat', null);
        $lng = Input::get('lng', null);

        if (is_null($booking_id) || is_null($lat) ||
            is_null($distance) || is_null($lng) || is_null($state)
        ) {
            return ErrorCode::errorMissingParam();
        }

        if (empty($booking_id) || !is_numeric($booking_id)) {
            return ErrorCode::errorParam('booking_id');
        }

        if (!is_numeric($distance) || $distance < 0) {
            return ErrorCode::errorParam('distance');
        }
        if (!is_numeric($lat) || $lat > 90 || $lat < -90) {
            return ErrorCode::errorParam('lat');
        }
        if (!is_numeric($lng) || $lng > 180 || $lng < -180) {
            return ErrorCode::errorParam('lng');
        }

        if (empty($state) || !is_numeric($state) || $state < 1 || $state > 4) {
            return ErrorCode::errorParam('state');
        }

        return DB::transaction(function () use ($booking_id, $state, $lat, $lng, $distance, $address, $driver_id,$unit) {
            return OrderStateAlgorithm::getOrderState()->driverChangeOrderState($driver_id, $booking_id,$unit, $state, $lat, $lng, $distance, $address);
        });
    }

    public function driverFinishOrder(Request $request, $booking_id)
    {
        /**
         * $msg = ["booking_id"=>$booking_id ,'driver_id'=>$driver_id,'content'=>'customer has arrival, this trip cost $'.$cost];
         */

        $company_id = $request->user->company_id;
        $booking = Booking::where("id", $booking_id)->first();
        $driver_id = $request->user->driver->id;

        if ($booking->exe_com_id != $company_id || $driver_id != $booking->driver_id) {
            return ErrorCode::errorDriverToChangeOrderPrice();
        }
        $trip_done = Input::get("active", 1);
        $order = Order::where('booking_id', $booking_id)->where("order_state", Order::ORDER_STATE_DONE)->first();
        if (empty($order)) {
            return ErrorCode::errorNotExist("booking");
        }
        if ($order->trip_state != Order::TRIP_STATE_WAITING_DRIVER_DETERMINE) {
            return ErrorCode::errorOrderTripState($order->trip_state);
        } else {
            $order->trip_state = Order::TRIP_STATE_WAITING_TO_SETTLE;
            $order->finish_time = MethodAlgorithm::formatTimestampToDate(time());
            $order->settle_time = MethodAlgorithm::formatTimestampToDate(time());
            $order->save();
        }

        if ($trip_done == 0) {
            $order->free_fee = $order->actual_fee - $booking->total_cost;
            $order->actual_fee = $booking->total_cost;
            $order->save();
        }

        $this->dispatch(new PayTripSettleJob($booking_id, $booking->company_id));

        return ErrorCode::success(["active" => $trip_done]);
    }


    public function changeOrderFeeModification(Request $request, $booking_id)
    {
//        if($order >= Order::TRIP_STATE_WAITING_TO_SETTLE){
//            return ErrorCode::errorNotExist();
//        }
    }

    public function bookingOrderArchive($booking_id)
    {
        $archive = Input::get('archive', null);
        if (!is_numeric($archive) ||
            ($archive != Order::ARCHIVE_TYPE_RESTORE &&
                $archive != Order::ARCHIVE_TYPE_ARCHIVE)
        ) {
            return ErrorCode::errorParam('archive');
        }

        $bill = Bill::where('booking_id', $booking_id)
            ->first();
        if (empty($bill)) {
            return ErrorCode::errorTripNotSettleFinished();
        }
        Order::where('booking_id', $booking_id)
            ->update(["archive" => $archive]);
        return ErrorCode::success('success');
    }

}