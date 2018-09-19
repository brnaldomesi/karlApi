<?php
/**
 * Created by PhpStorm.
 * User: lqh
 * Date: 2017/1/5
 * Time: 上午10:48
 */

namespace App\Method;


use App\ErrorCode;
use App\Jobs\PayTripSettleJob;
use App\Jobs\PushCustomerJob;
use App\Model\Booking;
use App\Model\Company;
use App\Model\CompanySetting;
use App\Model\Offer;
use App\Model\Order;
use App\Model\Track;
use App\PushMsg;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Lang;

class OrderStateAlgorithm
{
    private static $_instance;

    /**
     * OrderStateAlgorithm constructor.
     */
    private function __construct()
    {
    }

    /**
     * @return OrderStateAlgorithm
     */
    public static function getOrderState()
    {
        if (self::$_instance == null) {
            self::$_instance = new OrderStateAlgorithm();
        }
        return self::$_instance;
    }

    public function driverChangeOrderState($driverId, $bookingId,$unit ,$state, $lat, $lng, $distance, $address)
    {
        return $this->orderChangeState($driverId,$unit, $bookingId, $state, $lat, $lng, $distance, $address);
    }

    public function adminChangeOrderState($companyId,$bookingId, $adminId, $state)
    {
        $booking = Booking::where('company_id', $companyId)
            ->where('id', $bookingId)
            ->select(
                'driver_id'
            )
            ->first();
        if (empty($booking)) {
            return ErrorCode::errorNotExist('booking');
        }
        return $this->orderChangeState($booking->driver_id, $booking->unit,$bookingId, $state, 0, 0, 0, '', $adminId);
    }

    private function orderChangeState($driverId,$unit, $bookingId, $state, $lat, $lng, $distance, $address, $adminId = null)
    {
        //检查当前司机是否有正在执行的订单
        $orderState = [
            Order::ORDER_STATE_ADMIN_CANCEL,
            Order::ORDER_STATE_SUPER_ADMIN_CANCEL,
            Order::ORDER_STATE_PASSENGER_CANCEL,
            Order::ORDER_STATE_TIMES_UP_CANCEL,
            Order::ORDER_STATE_WAIT_DETERMINE,
        ];
        $orderCount = Order::leftjoin('bookings', 'bookings.id', '=', 'orders.booking_id')
            ->where('bookings.driver_id', $driverId)
            ->whereNotIn('orders.booking_id', [$bookingId])
            ->whereNotIn('orders.order_state', $orderState)
            ->whereBetween('trip_state', [Order::TRIP_STATE_DRIVE_TO_PICK_UP,
                Order::TRIP_STATE_WAITING_DRIVER_DETERMINE])
            ->count();
        if ($orderCount != 0) {
            return ErrorCode::errorDriverHasRunningOrder();
        }
        $order = Order::where('orders.booking_id', $bookingId)
            ->whereNotIn('orders.order_state', $orderState)
            ->first();
        if (empty($order)) {
            return ErrorCode::errorOrderStateError();
        }
        $booking = Booking::where([['bookings.id', $bookingId],
            ['bookings.driver_id', $driverId]])
            ->where("bookings.reject", Booking::REJECT_TYPE_NORMAL)
            ->leftjoin('drivers', 'drivers.id', '=', 'bookings.driver_id')
            ->leftjoin('users as duser', 'drivers.user_id', '=', 'duser.id')
            ->leftjoin('customers', 'customers.id', '=', 'bookings.customer_id')
            ->leftjoin('users as cuser', 'customers.user_id', '=', 'cuser.id')
            ->select('bookings.card_token',
                'bookings.company_id',
                'customers.id as customer_user_id',
                DB::raw("UNIX_TIMESTAMP(bookings.appointed_at) as appointed_at"),
                'bookings.type',
                'bookings.tva',
                'bookings.estimate_time',
                'bookings.free_fee',
                'bookings.a_address',
                'bookings.a_is_airport',
                'bookings.d_address',
                'bookings.d_is_airport',
                'bookings.offer_data',
                'bookings.option_data',
                'bookings.total_cost',
                'bookings.base_cost',
                'bookings.option_cost',
                "duser.last_name as name",
                "cuser.lang"
            )
            ->first();
        if (empty($booking)) {
            return (ErrorCode::errorNotExist('booking'));
        }
        $spreads = 0;
        app('translator')->setLocale($booking->lang);
        switch ($state) {
            case 1:    //司机出发
                if ($order->trip_state != Order::TRIP_STATE_WAIT_TO_DEPARTURE) {
                    return (ErrorCode::errorOrderTripState($order->trip_state));
                }
                \App\Method\PushCenter::initInstance()->sendAdminNotice("driverStartTripTitle", "driverStartTrip", $booking->company_id);
                $order->trip_state = Order::TRIP_STATE_DRIVE_TO_PICK_UP;
                $order->order_state = Order::ORDER_STATE_RUN;
                $line = 1;
                $order->departure_time = MethodAlgorithm::formatTimestampToDate(time());
                $msg = Lang::get("push_message.driverDeparture");
                $cost = 0;
                break;
            case 2:  //司机到达
                if ($order->trip_state != Order::TRIP_STATE_DRIVE_TO_PICK_UP) {
                    return (ErrorCode::errorOrderTripState($order->trip_state));
                }
                $order->trip_state = Order::TRIP_STATE_WAITING_CUSTOMER;
                $order->order_state = Order::ORDER_STATE_RUN;
                $line = 1;
                $order->reach_time = MethodAlgorithm::formatTimestampToDate(time());
                $msg = Lang::get("push_message.driverArrive");
                $cost = 0;
                break;
            case 3:   //乘客上车
                if (!is_null($adminId)) {
                    if ($order->trip_state != Order::TRIP_STATE_WAIT_TO_DEPARTURE) {
                        return (ErrorCode::errorOrderTripState($order->trip_state));
                    }
                    $order->admin_action = Order::ADMIN_ACTION_START;
                    $order->admin_id = $adminId;
                } else {
                    if ($order->trip_state != Order::TRIP_STATE_WAITING_CUSTOMER) {
                        return (ErrorCode::errorOrderTripState($order->trip_state));
                    }
                }
                $order->trip_state = Order::TRIP_STATE_GO_TO_DROP_OFF;
                $order->order_state = Order::ORDER_STATE_RUN;
                $line = 2;
                $order->start_time = MethodAlgorithm::formatTimestampToDate(time());
                $msg = Lang::get("push_message.clientDeparture");
                $cost = 0;
                break;
            case 4://乘客到达
                if (!is_null($adminId)) {
                    if ($order->trip_state > Order::TRIP_STATE_GO_TO_DROP_OFF) {
                        return (ErrorCode::errorOrderTripState($order->trip_state));
                    }
                } else {
                    if ($order->trip_state != Order::TRIP_STATE_GO_TO_DROP_OFF) {
                        return (ErrorCode::errorOrderTripState($order->trip_state));
                    }
                }
                $time = time();
                $order->trip_state = Order::TRIP_STATE_WAITING_DRIVER_DETERMINE;
                $order->order_state = Order::ORDER_STATE_DONE;
                $line = 2;
                $order->finish_time = MethodAlgorithm::formatTimestampToDate($time);
                $order->actual_distance = $distance;
                $order->actual_time = ($time - strtotime($order->start_time)) / 60;
                $offer = json_decode($booking->offer_data, true);
                if ($booking->type == Offer::CHECK_TYPE_CUSTOM) {
                    $cost = $booking->total_cost;
                } else {
                    //实际路程花费
                    $cost = PaymentMethod::offerPriceSettlement(
                            $offer['cost_min'],
                            $offer['calc_method'],
                            $order->actual_time,
                            $distance,
                            isset($offer['price']) ? $offer['price'] : $offer['prices'],
                            $unit,
                            isset($offer['unit']) ? $offer['unit'] : CompanySetting::UNIT_MI,
                            $order->d_is_airport,
                            isset($offer['d_port_price']) ? $offer['d_port_price'] : 0,
                            is_null($order->a_is_airport) ? 0 : $order->a_is_airport,
                            isset($offer['a_port_price']) ? $offer['a_port_price'] : 0
                        ) * (1 + $booking->tva / 100);
                    //实际路程花费和预定时预估路程花费差价
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
                }


                $spreads = $cost - $booking->base_cost;

                $company = Company::leftjoin('company_settings', 'company_settings.company_id', '=', 'companies.id')
                    ->where('companies.id', $booking->company_id)
                    ->select('companies.name', 'company_settings.settle_type')
                    ->first();

                $msg = Lang::get("push_message.clientArrive",["name"=>$company->name]);
                if (!is_null($adminId) ||
                    $order->admin_action == Order::ADMIN_ACTION_START
                ) {
                    $order->trip_state = Order::TRIP_STATE_WAITING_TO_SETTLE;
                    $order->actual_fee = $booking->total_cost;
                    $order->admin_action = Order::ADMIN_ACTION_END;
                    $order->admin_id = $adminId;
                    $order->save();
                    $spreads = 0;
                    dispatch(new PayTripSettleJob($bookingId, $booking->company_id));
                } else {
                    switch ($company->settle_type) {
                        case CompanySetting::SETTLE_TYPE_IGNORE:
                            $order->trip_state = Order::TRIP_STATE_WAITING_TO_SETTLE;
                            $order->actual_fee = $booking->total_cost;
                            $order->save();
                            $spreads = 0;
                            dispatch(new PayTripSettleJob($bookingId, $booking->company_id));
                            break;
                        case CompanySetting::SETTLE_TYPE_ADD:
                            $order->trip_state = Order::TRIP_STATE_WAITING_TO_SETTLE;
                            if ($spreads < 1) {
                                $spreads = 0;
                                //实际花费=预定总费用
                                $order->actual_fee = $booking->total_cost;
                            } else {
                                $order->actual_fee = $cost + $booking->option_cost;
                            }
                            $order->save();
                            dispatch(new PayTripSettleJob($bookingId, $booking->company_id));
                            break;
                        case CompanySetting::SETTLE_TYPE_DRIVER:
                            if ($spreads < 1) {
                                $order->trip_state = Order::TRIP_STATE_WAITING_TO_SETTLE;
                                $spreads = 0;
                                //实际花费=预定总费用
                                $order->actual_fee = $booking->total_cost;
                                $order->save();
                                dispatch(new PayTripSettleJob($bookingId, $booking->company_id));
                            } else {
                                //实际花费=实际路程花费+预定option花费
                                $order->actual_fee = $cost + $booking->option_cost;
                            }
                            break;
                    }
                }
                break;
            default:
                return (ErrorCode::errorParam('state'));
        }


        $order->save();
        Track::create([
            "order_id" => $order->id,
            "line" => $line,
            'address' => $address,
            "lat" => $lat,
            "lng" => $lng,
            "trip_cost" => $cost,
            "distance" => $distance,
            "pointed_at" => MethodAlgorithm::formatTimestampToDate(time())
        ]);
        $pushJob = new PushCustomerJob($booking->customer_user_id, $msg);
        dispatch($pushJob);
        $order->departure_time = strtotime($order->departure_time);
        $order->reach_time = strtotime($order->reach_time);
        $order->start_time = strtotime($order->start_time);
        $order->finish_time = strtotime($order->finish_time);
        $order->spreads = $spreads;
        unset($order->id);
        return ErrorCode::success($order);
    }


    public function computerTripPrice()
    {

    }
}