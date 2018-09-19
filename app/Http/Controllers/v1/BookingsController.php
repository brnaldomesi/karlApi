<?php

namespace App\Http\Controllers\v1;
use App\Method\GeoLocationAlgorithm;
use App\Jobs\BookingStatisticJob;
use App\Jobs\SendEmailAdminBookingSendBackJob;
use App\Model\BookingChangeHistory;
use App\ErrorCode;
use App\Jobs\PushBookingCancelJob;
use App\Jobs\SendEmailCustomerBookingJob;
use App\Method\MethodAlgorithm;
use App\Method\PaymentAlgorithm;
use App\Method\UrlSpell;
use App\Model\Bill;
use App\Model\Booking;
use App\Model\CalendarEvent;
use App\Constants;
use App\Model\Company;
use App\Jobs\PushBookingSuccessJob;
use App\Model\Order;
use App\Method\KARLDateTime;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Lang;

class BookingsController extends Controller
{


    public function getBookings()
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

        $count = Booking::all()->count();


        $bookings =
            Booking::leftjoin('companies', 'companies.id', '=', 'bookings.company_id')
                ->leftjoin('orders', 'orders.booking_id', '=', 'bookings.id')
                ->leftjoin('customers', 'customers.id', '=', 'bookings.customer_id')
                ->leftjoin('users', 'users.id', '=', 'customers.user_id')
                ->select(
                    'companies.name as company_name', 'users.first_name',
                    'users.last_name',
                    DB::raw("case when orders.trip_state>" . Order::TRIP_STATE_WAITING_TO_SETTLE . "
                 then
                  orders.actual_fee
                 else
                  bookings.total_cost
                  end as cost"),
                    "orders.trip_state",
                    'bookings.type'
                )
                ->skip($skip)
                ->take($per_page)
                ->get();
        if ($bookings->count() == 0) {
            return ErrorCode::successEmptyResult('booking ');
        } else {
            $result['total'] = $count;
            $result['bookings'] = $bookings;
            return ErrorCode::success($result);
        }

    }


    public function getBookingDetail(Request $request, $booking_id)
    {
        $token = $request->user->token;
        $company_id = $request->user->company_id;
        $booking = Booking::getBookingDetail($booking_id, $company_id);
        if (is_null($booking) || empty($booking)) {
            return ErrorCode::errorNotExist('booking');
        } else {
            $customer = json_decode($booking->customer_data);
            $booking->c_first_name = $customer->first_name;
            $booking->c_last_name = $customer->last_name;
            $booking->c_gender = $customer->gender;
            $booking->c_mobile = $customer->mobile;
            $booking->c_email = $customer->email;
            $booking->c_avatar_url = $customer->avatar_url;

            return ErrorCode::success($booking);
        }
    }


//    public function deleteBookings(Request $request, $booking_id)
//    {
//        $company_id = $request->user->company_id;
//        $booking = Booking::where('id', $booking_id)
//            ->first();
//        if (empty($booking)) {
//            return ErrorCode::errorNotExist('booking');
//        }
//        if ($company_id != $booking->company_id) {
//            return ErrorCode::errorAdminUnauthorizedOperation();
//        }
//        //1.æ£€æŸ¥è®¢å•æ˜¯å¦å·²æ‰§è¡Œ,ä¹˜å®¢æ²¡ä¸Šè½¦ä¹‹å‰
//        $order = Order::where([
//            ['booking_id', $booking->id],
//            ['trip_state', ">=", Order::TRIP_STATE_GO_TO_DROP_OFF]
//        ])->first();
//        if (!empty($order)) {
//            return ErrorCode::errorOrderHasBeenComplied();
//        }
//        $canceled = $this->cancelBooking($booking);
//        if ($canceled) {
//            return ErrorCode::success('success');
//        } else {
//            return ErrorCode::errorDB('cancel booking');
//        }
//    }

//    private function cancelBooking($booking, $refund = true)
//    {
//        try {
//            DB::transaction(function () use ($booking, $refund) {
//                //2.é‡Šæ”¾è½¦è¾†å¸æœºäº‹ä»¶,
//                $driverEvent = CalendarEvent::where([
//                    ['re_owner_id', $booking->driver_id],
//                    ['re_type', Calendar::DRIVER_TYPE],
//                    ['creator_id', $booking->booking_id],
//                    ['creator_TYPE', CalendarEvent::CREATOR_TYPE_BOOKING]
//                ])
//                    ->first();
//                $driverEvent->enable = CalendarEvent::EVENT_DISABLE;
//                $driverEvent->save();
//                $carEvent = CalendarEvent::where([
//                    ['re_owner_id', $booking->car_id],
//                    ['re_type', Calendar::CAR_TYPE],
//                    ['creator_id', $booking->booking_id],
//                    ['creator_TYPE', CalendarEvent::CREATOR_TYPE_BOOKING]
//                ])->first();
//                $carEvent->enable = CalendarEvent::EVENT_DISABLE;
//                $carEvent->save();
//                //3.é€€æ¬¾
//                if ($refund) {
//                    try {
//                        PaymentAlgorithm::getPayment()->refundBooking($booking->booking_id,
//                            PaymentAlgorithm::PRE_CHARGE_REFUND, $booking->pay1_amount, $booking->company_id);
//                    } catch (\Exception $ex) {
////                        Log::error($ex);
//                    }
//                }
//                //4.åˆ é™¤è®¢å• booking,order
//                $order = Order::where('booking_id', $booking->booking_id)->first();
//                $order->order_state = Order::ORDER_STATE_ADMIN_CANCEL;
//                $order->save();
//            });
//            return true;
//        } catch (\Exception $ex) {
//            return false;
//        }
//    }

    public function bookingDetermine($booking_sn, $key,$lang)
    {
        $booking = Booking::where(DB::raw("md5(id)"), $booking_sn)
            ->where("custom_auth_code", $key)
            ->first();
        app('translator')->setLocale($lang);
        if (empty($booking)) {
            $msg = Lang::get("booking.bookingNotExist");
            return view('confirm', ['message' => $msg]);
        }

        $order = Order::where('orders.booking_id', $booking->id)
            ->where('orders.order_state', Order::ORDER_STATE_WAIT_DETERMINE)
            ->first();
        if (empty($order)) {
            $msg = Lang::get("booking.bookingTimeout");
            return view('confirm', ['message' => $msg]);
        }
        try {
            PaymentAlgorithm::getPayment()->bookingCharge(
                $booking->total_cost, $booking->tva, $booking->id,
                $booking->customer_id, $booking->card_token, $booking->exe_com_id,
                $booking->company_id!=$booking->exe_com_id
                );

            $order->order_state = Order::ORDER_STATE_BOOKING;
            $order->save();
            $booking->custom_auth_code = '';
            $booking->save();
            $msg = Lang::get("booking.bookingSuccess");
            $job = new PushBookingSuccessJob($booking->customer_id, $booking->driver_id);
            $this->dispatch($job);
        } catch (\Exception $ex) {
            $msg = Lang::get("booking.payFault");
        }

        return view('custom_quote_confirm', ['message' => $msg]);
    }


    public function endBooking(Request $request, $booking_id)
    {
        $company_id = $request->user->company_id;
        $booking = Booking::where('bookings.id', $booking_id)
            ->leftjoin('booking_transaction_histories as bth', "bth.booking_id", '=', 'bookings.id')
            ->select("bookings.id as booking_id", "bookings.company_id", "bookings.offer_data", "bookings.driver_id",
                "bookings.car_id", "bth.pay1_amount", "bth.pay1_refund_success", "bth.pay1_refund_amount",
                "bth.pay2_success", "bth.pay2_amount"
            )
            ->first();
        if (empty($booking)) {
            return ErrorCode::errorNotExist('booking');
        }
        if ($company_id != $booking->company_id) {
            return ErrorCode::errorAdminUnauthorizedOperation();
        }
        $order = Order::where('booking_id', $booking_id)->first();
        if (empty($order)) {
            return ErrorCode::errorDB();
        }

        try {
            DB::transaction(function () use ($order, $booking, $company_id) {
                //ç›®å‰ä¸å­˜åœ¨å¸æœºæœªæ‰§è¡Œå’Œè®¢å•å®ŒæˆçŠ¶æ€
                //$order->order_state = Order::ORDER_STATE_DRIVER_UNRUN
                //$order->order_state = Order::ORDER_STATE_SETTLE_ERROR
                //èŽ·å–å½“å‰è®¢å•çŠ¶æ€
                $currentState = $order->order_state;
                //ä¿®æ”¹å½“å‰è®¢å•ä¸ºå–æ¶ˆ
                $order->order_state = Order::ORDER_STATE_ADMIN_CANCEL;
                $order->save();

                if (
                    $currentState == Order::ORDER_STATE_ADMIN_CANCEL ||
                    $currentState == Order::ORDER_STATE_SUPER_ADMIN_CANCEL ||
                    $currentState == Order::ORDER_STATE_PASSENGER_CANCEL ||
                    $currentState == Order::ORDER_STATE_TIMES_UP_CANCEL
                ) {
                    return ErrorCode::errorOrderHasBeenCanceled();
                } else if ($currentState == Order::ORDER_STATE_BOOKING) {
                    //å·²é¢„è®¢ï¼Œæœªæ‰§è¡Œï¼Œé‡Šæ”¾è½¦å’Œå¸æœºäº‹ä»¶,é€€æ¬¾ å¤±è´¥æ€Žä¹ˆåŠžï¼Ÿ
                    CalendarEvent::where([
                        ['creator_id', $booking->booking_id],
                        ['creator_TYPE', CalendarEvent::CREATOR_TYPE_BOOKING]
                    ])->update(["enable" => CalendarEvent::EVENT_DISABLE]);
                    PaymentAlgorithm::getPayment()->refundBooking($booking->booking_id,
                        PaymentAlgorithm::PRE_CHARGE_REFUND,
                        $booking->pay1_amount, $company_id);
                } else if ($currentState == Order::ORDER_STATE_RUN) {
                    CalendarEvent::where([
                        ['creator_id', $booking->booking_id],
                        ['creator_TYPE', CalendarEvent::CREATOR_TYPE_BOOKING]
                    ])->update(["enable" => CalendarEvent::EVENT_DISABLE]);
                    //ä¹˜å®¢æœªä¸Šè½¦é€€é’±,é‡Šæ”¾äº‹ä»¶
                    switch ($order->trip_state) {
                        case Order::TRIP_STATE_WAIT_TO_DEPARTURE:
                        case Order::TRIP_STATE_DRIVE_TO_PICK_UP:
                        case Order::TRIP_STATE_WAITING_CUSTOMER:
                            PaymentAlgorithm::getPayment()->refundBooking($booking->booking_id,
                                PaymentAlgorithm::PRE_CHARGE_REFUND,
                                $booking->pay1_amount, $company_id);
                            break;
                        //ä¹˜å®¢å·²ä¸Šè½¦ä¸é€€æ¬¾
                        //ç”ŸæˆBill
                        case Order::TRIP_STATE_GO_TO_DROP_OFF:
                            $actualCost = $booking->pay1_amount;
                            $companyRate = Company::where("id", $company_id)->select("rate")->first();
                            //å¹³å°æ ¹æ®å…¬å¸ä¸åŒæŠ½å–å¹³å°æ¯”ä¾‹ï¼Œ
                            $platFee = round($actualCost * $companyRate->rate, 2);
                            //å‰©ä½™
                            $ownFee = $actualCost - $platFee;
                            //an
                            if ($booking->company_id != $booking->exe_com_id) {
                                //ç®—å‡ºç¨Žè´¹
                                $tvaFee = round($actualCost / (1 + $booking->tva / 100) * ($booking->tva / 100), 2);
                                //anè´¹ç”¨ç­‰äºŽå‰©ä½™è´¹ç”¨å‡ºåŽ»ç¨Žè´¹çš„%85 å†åŠ ä¸Šç¨Žè´¹
                                $anFee = round(($ownFee - $tvaFee) * Constants::EXE_COMPANY_TVA, 2) + $tvaFee;
                                $income = $ownFee - $anFee;
                            } else {
                                //éžAN
                                $anFee = 0.00;
                                $income = $ownFee;
                            }
                            Bill::create([
                                "booking_id" => $booking->booking_id,
                                "order_actual_fee" => $actualCost,
                                "settle_fee" => $actualCost,
                                "com_income" => $income,
                                "platform_income" => $platFee,
                                "an_fee" => $anFee,
                                "settle_time" => MethodAlgorithm::formatTimestampToDate(time())
                            ]);
                            $this->dispatch(new BookingStatisticJob($booking->booking_id));
                            break;
                        default:
                            throw new \Exception(ErrorCode::errorOrderStateError());
                            break;
                    }

                } else if ($currentState == Order::ORDER_STATE_SETTLE_ERROR) {
                    if (empty($booking->pay1_refund_success) || empty($booking->pay2_success)) {
                        $order->actual_fee = $booking->pay1_amount;
                        $order->save();
                        $actualCost = $booking->pay1_amount;
                        $companyRate = Company::where("id", $company_id)->select("rate")->first();
                        //å¹³å°æ ¹æ®å…¬å¸ä¸åŒæŠ½å–å¹³å°æ¯”ä¾‹ï¼Œ
                        $platFee = round($actualCost * $companyRate->rate, 2);
                        //å‰©ä½™
                        $ownFee = $actualCost - $platFee;
                        //an
                        if ($booking->company_id != $booking->exe_com_id) {
                            //ç®—å‡ºç¨Žè´¹
                            $tvaFee = round($actualCost / (1 + $booking->tva / 100) * ($booking->tva / 100), 2);
                            //anè´¹ç”¨ç­‰äºŽå‰©ä½™è´¹ç”¨å‡ºåŽ»ç¨Žè´¹çš„%85 å†åŠ ä¸Šç¨Žè´¹
                            $anFee = round(($ownFee - $tvaFee) * Constants::EXE_COMPANY_TVA, 2) + $tvaFee;
                            $income = $ownFee - $anFee;
                        } else {
                            //éžAN
                            $anFee = 0.00;
                            $income = $ownFee;
                        }
                        Bill::create([
                            "booking_id" => $booking->booking_id,
                            "order_actual_fee" => $actualCost,
                            "settle_fee" => $actualCost,
                            "com_income" => $income,
                            "platform_income" => $platFee,
                            "an_fee" => $anFee,
                            "settle_time" => MethodAlgorithm::formatTimestampToDate(time())
                        ]);
                        $this->dispatch(new BookingStatisticJob($booking->booking_id));

                    }
                } else if ($currentState == Order::ORDER_STATE_DONE) {
                    //
                    switch ($order->trip_state) {
                        case Order::TRIP_STATE_WAITING_DRIVER_DETERMINE:
                        case Order::TRIP_STATE_WAITING_TO_SETTLE:
                            $actualCost = $booking->pay1_amount;
                            $companyRate = Company::where("id", $company_id)->select("rate")->first();
                            //å¹³å°æ ¹æ®å…¬å¸ä¸åŒæŠ½å–å¹³å°æ¯”ä¾‹ï¼Œ
                            $platFee = round($actualCost * $companyRate->rate, 2);
                            //å‰©ä½™
                            $ownFee = $actualCost - $platFee;
                            //an
                            if ($booking->company_id != $booking->exe_com_id) {
                                //ç®—å‡ºç¨Žè´¹
                                $tvaFee = round($actualCost / (1 + $booking->tva / 100) * ($booking->tva / 100), 2);
                                //anè´¹ç”¨ç­‰äºŽå‰©ä½™è´¹ç”¨å‡ºåŽ»ç¨Žè´¹çš„%85 å†åŠ ä¸Šç¨Žè´¹
                                $anFee = round(($ownFee - $tvaFee) * Constants::EXE_COMPANY_TVA, 2) + $tvaFee;
                                $income = $ownFee - $anFee;
                            } else {
                                //éžAN
                                $anFee = 0.00;
                                $income = $ownFee;
                            }
                            Bill::create([
                                "booking_id" => $booking->booking_id,
                                "order_actual_fee" => $actualCost,
                                "settle_fee" => $actualCost,
                                "com_income" => $income,
                                "platform_income" => $platFee,
                                "an_fee" => $anFee,
                                "settle_time" => MethodAlgorithm::formatTimestampToDate(time())
                            ]);
                            $this->dispatch(new BookingStatisticJob($booking->booking_id));

                            break;
                        case Order::TRIP_STATE_SETTLING:
                            throw new \Exception(ErrorCode::errorTripInSettle());
                            break;
                        case Order::TRIP_STATE_SETTLE_DONE:
                            throw new \Exception(ErrorCode::errorOrderTripHasBeenFinished());
                            break;

                    }

                } else if ($currentState == Order::ORDER_STATE_WAIT_DETERMINE) {
                    //å·²é¢„è®¢ï¼Œä½†æ˜¯æœªç¡®è®¤è®¢å•ï¼Œä¸éœ€è¦é€€æ¬¾
                    CalendarEvent::where([
                        ['creator_id', $booking->booking_id],
                        ['creator_TYPE', CalendarEvent::CREATOR_TYPE_BOOKING]
                    ])->update(["enable" => CalendarEvent::EVENT_DISABLE]);
                }
            });
            //æŽ¨é€
            $this->dispatch(new PushBookingCancelJob($booking_id));
            return ErrorCode::success('success');
        } catch (\Exception $ex) {
            return $ex->getMessage();
        }
    }


    public function companiesGetBookings(Request $request)
    {
        $company_id = $request->user->company_id;
        $token = $request->user->token;
        $startTime = Input::get('start_time', null);
        $endTime = Input::get('end_time', '');
        $search = Input::get('search', "%");
        $tripState = Input::get('trip_state', null);
        $filter = Input::get('filter', Constants::BOOK_FILTER_ALL);
        $orderState = Input::get('order_state', null);
        $page = Input::get('page', Constants::PAGE_DEFAULT);
        $per_page = Input::get('per_page', Constants::PER_PAGE_DEFAULT);
        $orderBy = Input::get('order_by', 0);
        $timezone = Input::get('timezone', 'GMT');  


        if(!is_null($startTime)) {

            $startDate = new \DateTime("@{$startTime}");
            $startDate->setTimezone(new \DateTimeZone($timezone));
            $startDateStr = "'" . $startDate->format('Y-m-d H:i:s') . "'";
        }
        else
            $startDateStr = $startTime; 

        if(!is_null($endTime))
        {
            $endDate = new \DateTime("@{$endTime}");
            $endDate->setTimezone(new \DateTimeZone($timezone));
            $endDateStr = "'" . $endDate->format('Y-m-d H:i:s') . "'";
        }
        else
            $endDateStr = $endTime;
    
        if (!is_numeric($orderBy) || ($orderBy != Constants::ORDER_BY_ASC && $orderBy != Constants::ORDER_BY_DESC)) {
            return ErrorCode::errorParam('order_by');
        } else {
            $orderBy = $orderBy == 0 ? 'asc' : 'desc';
        }
        if ($per_page > 100 || $per_page < 1 || !is_numeric($per_page)) {
            $per_page = Constants::PER_PAGE_DEFAULT;
        }
        if ($page < 1 || !is_numeric($page)) {
            $page = Constants::PAGE_DEFAULT;
        }
        if (!is_numeric($startTime) || $startTime < 0) {
            return ErrorCode::errorParam('start_time');
        }

        if (!is_numeric($endTime) || $endTime < 0) {
            $endTime = time() + 365 * 24 * 3600;
        }

        if (empty($tripState)) {
            $tripState = [
                Order::TRIP_STATE_WAIT_TO_DEPARTURE,
                Order::TRIP_STATE_DRIVE_TO_PICK_UP,
                Order::TRIP_STATE_WAITING_CUSTOMER,
                Order::TRIP_STATE_GO_TO_DROP_OFF,
                Order::TRIP_STATE_WAITING_DRIVER_DETERMINE,
                Order::TRIP_STATE_WAITING_TO_SETTLE,
                Order::TRIP_STATE_SETTLING,
                Order::TRIP_STATE_SETTLE_DONE
            ];
        } else {
            $tripState = explode(',', $tripState);
        }

        if (empty($orderState)) {
            $orderState = [
                Order::ORDER_STATE_BOOKING,
                Order::ORDER_STATE_RUN,
                Order::ORDER_STATE_DRIVER_UNRUN,
                Order::ORDER_STATE_SETTLE_ERROR,
                Order::ORDER_STATE_DONE,
                Order::ORDER_STATE_ADMIN_CANCEL,
                Order::ORDER_STATE_SUPER_ADMIN_CANCEL,
                Order::ORDER_STATE_PASSENGER_CANCEL,
                Order::ORDER_STATE_TIMES_UP_CANCEL,
                Order::ORDER_STATE_WAIT_DETERMINE,
            ];
        } else {
            $orderState = explode(',', $orderState);
        }
        $skip = $per_page * ($page - 1);


    


        $bookingCount = Booking::leftjoin('orders', 'orders.booking_id', '=', 'bookings.id')
//            ->leftjoin('booking_transaction_histories as bth', 'bth.booking_id', '=', 'bookings.id')
            ->leftjoin('customers', 'customers.id', '=', 'bookings.customer_id')
            ->leftjoin('users', 'users.id', '=', 'customers.user_id')
//            ->where('bookings.reject',Booking::REJECT_TYPE_NORMAL)
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
            ->whereRaw("bookings.appointed_at_pickup>={$startDateStr}")
            ->whereRaw("bookings.appointed_at_pickup<{$endDateStr}")
            ->whereIn('orders.order_state', $orderState)
            ->whereIn('orders.trip_state', $tripState)
            ->where(function ($query) use ($search) {
                $query->where('bookings.d_address', 'like', "%" . $search . "%")
                    ->orWhere('bookings.a_address', 'like', "%" . $search . "%")
                    ->orWhere('users.first_name', 'like', "%" . $search . "%")
                    ->orWhere('users.last_name', 'like', "%" . $search . "%")
                    ->orWhere('users.mobile', 'like', "%" . $search . "%")
                    ->orWhere('users.email', 'like', "%" . $search . "%");

            })
            ->distinct('bookings.id')
            ->count('bookings.id');


        $bookings = Booking::leftjoin('orders', 'orders.booking_id', '=', 'bookings.id')
            ->leftjoin('booking_transaction_histories as bth', 'bth.booking_id', '=', 'bookings.id')
            ->leftjoin('customers', 'customers.id', '=', 'bookings.customer_id')
            ->leftjoin("booking_airlines", "bookings.id", "=", "booking_airlines.booking_id")
            ->leftjoin('users', 'users.id', '=', 'customers.user_id')
            ->leftjoin('companies as ec', "ec.id", "=", "bookings.exe_com_id")
            ->leftjoin('companies as oc', "oc.id", "=", "bookings.company_id")
//            ->where('bookings.reject',Booking::REJECT_TYPE_NORMAL)
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
            ->whereRaw("bookings.appointed_at_pickup>={$startDateStr}")
            ->whereRaw("bookings.appointed_at_pickup<{$endDateStr}")
            ->whereIn('orders.order_state', $orderState)
            ->whereIn('orders.trip_state', $tripState)
            ->where(function ($query) use ($search) {
                $query->where('bookings.d_address', 'like', "%" . $search . "%")
                    ->orWhere('bookings.a_address', 'like', "%" . $search . "%")
                    ->orWhere('users.first_name', 'like', "%" . $search . "%")
                    ->orWhere('users.last_name', 'like', "%" . $search . "%")
                    ->orWhere('users.mobile', 'like', "%" . $search . "%")
                    ->orWhere('users.email', 'like', "%" . $search . "%");
            })
            ->select(
                'bookings.company_id',
                'bookings.car_data',
                'bookings.driver_data',
                'bookings.customer_data',
                'bookings.option_data',
                'bookings.offer_data',
                'bookings.reject',
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
                DB::raw('UNIX_TIMESTAMP(bookings.appointed_at) as temp_appointed_at'),
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
                'bookings.unit',
                'bth.ccy',
                'bookings.message',
                'bookings.coupon',
                'bookings.coupon_off',
                'bookings.estimate_time',
                'bookings.estimate_distance',
                'bookings.passenger_count',
                'bookings.bags_count',
                'bookings.passenger_names',
                'booking_airlines.a_airline',
                'booking_airlines.d_airline',
                'booking_airlines.a_flight',
                'booking_airlines.d_flight',
                'oc.name as own_company_name',
                'oc.id as own_company_id',
                'users.lang',
                DB::raw(UrlSpell::getUrlSpell()->getCompaniesLogoByNameInDB('own_company_logo', 'oc')),
                'ec.name as exe_company_name',
                'ec.id as exe_company_id',
                DB::raw(UrlSpell::getUrlSpell()->getCompaniesLogoByNameInDB('exe_company_logo', 'ec'))
            )
            ->skip($skip)
            ->take($per_page)
            ->orderBy('bookings.appointed_at', $orderBy)
            ->groupBy('bookings.id')
            ->get();

        if ($bookingCount == 0) {
            return ErrorCode::successEmptyResult('');
        } else {
            foreach ($bookings as $booking) {
                $customer = json_decode($booking->customer_data);
                $booking->c_first_name = $customer->first_name;
                $booking->c_last_name = $customer->last_name;
                $booking->c_gender = $customer->gender;
                $booking->c_mobile = $customer->mobile;
                $booking->c_email = $customer->email;
                $booking->c_avatar_url = $customer->avatar_url;

                $timeInfo = "";

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

                $date = new \DateTime("@{$booking->appointed_at}");
                $date->setTimezone(new \DateTimeZone($timezone));
                
                $booking->temp_appointed_at = $date->format("ha");

                //var_dump($time) ;
            }

            $result = ['total' => $bookingCount, 'bookings' => $bookings];
            return ErrorCode::success($result);
        }

    }

    public function customersGetBookings(Request $request)
    {
        $company_id = $request->user->company_id;
        $token = $request->user->token;
        $customer_id = $request->user->customer->id;
        $startTime = Input::get('start_time', null);
        $endTime = Input::get('end_time', '');
        $tripState = Input::get('trip_state', null);
        $orderState = Input::get('order_state', null);
        $page = Input::get('page', Constants::PAGE_DEFAULT);
        $per_page = Input::get('per_page', Constants::PER_PAGE_DEFAULT);
        $orderBy = Input::get('order_by', 0);
        if (!is_numeric($orderBy) || ($orderBy != Constants::ORDER_BY_ASC && $orderBy != Constants::ORDER_BY_DESC)) {
            return ErrorCode::errorParam('order_by');
        } else {
            $orderBy = $orderBy == 0 ? 'asc' : 'desc';
        }
        if ($per_page > 100 || $per_page < 1 || !is_numeric($per_page)) {
            $per_page = Constants::PER_PAGE_DEFAULT;
        }
        if ($page < 1 || !is_numeric($page)) {
            $page = Constants::PAGE_DEFAULT;
        }
        if (!is_numeric($startTime) || $startTime < 0) {
            return ErrorCode::errorParam('start_time');
        }

        if (!is_numeric($endTime) || $endTime < 0) {
            $endTime = time() + 365 * 24 * 3600;
        }
        if (empty($tripState)) {
            $tripState = [
                Order::TRIP_STATE_WAIT_TO_DEPARTURE,
                Order::TRIP_STATE_DRIVE_TO_PICK_UP,
                Order::TRIP_STATE_WAITING_CUSTOMER,
                Order::TRIP_STATE_GO_TO_DROP_OFF,
                Order::TRIP_STATE_WAITING_DRIVER_DETERMINE,
                Order::TRIP_STATE_WAITING_TO_SETTLE,
                Order::TRIP_STATE_SETTLING,
                Order::TRIP_STATE_SETTLE_DONE
            ];
        } else {
            $tripState = explode(',', $tripState);
        }
        if (empty($orderState)) {
            $orderState = [
                Order::ORDER_STATE_BOOKING,
                Order::ORDER_STATE_RUN,
                Order::ORDER_STATE_DRIVER_UNRUN,
                Order::ORDER_STATE_SETTLE_ERROR,
                Order::ORDER_STATE_DONE,
                Order::ORDER_STATE_ADMIN_CANCEL,
                Order::ORDER_STATE_SUPER_ADMIN_CANCEL,
                Order::ORDER_STATE_PASSENGER_CANCEL,
                Order::ORDER_STATE_TIMES_UP_CANCEL,
                Order::ORDER_STATE_WAIT_DETERMINE
            ];
        } else {
            $orderState = explode(',', $orderState);
        }

        $skip = $per_page * ($page - 1);
        $bookingCount = Booking::leftjoin('orders', 'orders.booking_id', '=', 'bookings.id')
//            ->leftjoin('booking_transaction_histories as bth', 'bth.booking_id', '=', 'bookings.id')
            ->where('bookings.company_id', $company_id)
            ->where('bookings.customer_id', $customer_id)
            ->leftjoin('companies as ec', "ec.id", "=", "bookings.exe_com_id")
            ->leftjoin('companies as oc', "oc.id", "=", "bookings.company_id")
            ->whereRaw("unix_timestamp(bookings.appointed_at)>={$startTime}")
            ->whereRaw("unix_timestamp(bookings.appointed_at)<{$endTime}")
            ->whereIn('orders.trip_state', $tripState)
            ->whereIn('orders.order_state', $orderState)
            ->count();
        $bookings = Booking::leftjoin('orders', 'orders.booking_id', '=', 'bookings.id')
            ->leftjoin("booking_airlines", "bookings.id", "=", "booking_airlines.booking_id")
            ->leftjoin('booking_transaction_histories as bth', 'bth.booking_id', '=', 'bookings.id')
            ->leftjoin('companies as ec', "ec.id", "=", "bookings.exe_com_id")
            ->leftjoin('companies as oc', "oc.id", "=", "bookings.company_id")
            ->where('bookings.company_id', $company_id)
            ->where('bookings.customer_id', $customer_id)
            ->whereRaw("unix_timestamp(bookings.appointed_at)>={$startTime}")
            ->whereRaw("unix_timestamp(bookings.appointed_at)<{$endTime}")
            ->whereIn('orders.trip_state', $tripState)
            ->whereIn('orders.order_state', $orderState)
            ->select(
                'bookings.company_id',
                'bookings.car_data',
                'bookings.driver_data',
                'bookings.option_data',
                'bookings.offer_data',
                DB::raw('
                    CASE WHEN orders.trip_state > ' . Order::TRIP_STATE_WAITING_TO_SETTLE . ' THEN
                        bth.pay1_amount+bth.pay2_amount
                    ELSE
                        if(bookings.total_cost - round(bookings.coupon_off*(1+bookings.tva/100),2) <0 ,
                        0, 
                        if(bookings.total_cost - round(bookings.coupon_off*(1+bookings.tva/100),2)>0&&bookings.total_cost - round(bookings.coupon_off*(1+bookings.tva/100),2)<1,
                        1,
                        bookings.total_cost))
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
                DB::raw("if(orders.settle_time is not null,1,0) as trip_done"),
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
                'bookings.estimate_time',
                'bookings.estimate_distance',
                'bookings.message',
                'bookings.coupon',
                'bookings.coupon_off',
                'bookings.passenger_count',
                'bookings.bags_count',
                'bookings.passenger_names',
                'bookings.unit',
                'bth.ccy',
                'booking_airlines.a_airline',
                'booking_airlines.d_airline',
                'booking_airlines.a_flight',
                'booking_airlines.d_flight',
                'oc.name as own_company_name',
                'oc.id as own_company_id',
                DB::raw(UrlSpell::getUrlSpell()->getCompaniesLogoByNameInDB('own_company_logo', 'oc')),
                'ec.name as exe_company_name',
                'ec.id as exe_company_id',
                DB::raw(UrlSpell::getUrlSpell()->getCompaniesLogoByNameInDB('exe_company_logo', 'ec'))
            )
            ->skip($skip)
            ->take($per_page)
            ->orderBy('bookings.appointed_at', $orderBy)
            ->get();



        if ($bookingCount == 0) {
            return ErrorCode::successEmptyResult('');
        } else {

            foreach ($bookings as $booking) {
                
                $timeInfo = "";

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

                $date = new \DateTime("@{$booking->appointed_at}");
                $date->setTimezone(new \DateTimeZone($timezone));
                
                $booking->temp_appointed_at = $date->format("Y-m-d H:ia");

                $booking->temp_list_appointed_at = $date->format("F, l d, Y");

                //var_dump($time) ;
            }

            $result = ['total' => $bookingCount, 'bookings' => $bookings];
            return ErrorCode::success($result);
        }
    }

    public function driversGetBookings(Request $request)
    {
        $company_id = $request->user->company_id;
        $token = $request->user->token;
        $driver_id = $request->user->driver->id;
        $startTime = Input::get('start_time', null);
        $endTime = Input::get('end_time', '');
        $search = Input::get('search', "%");
        $tripState = Input::get('trip_state', null);
        $page = Input::get('page', Constants::PAGE_DEFAULT);
        $per_page = Input::get('per_page', Constants::PER_PAGE_DEFAULT);
        $orderBy = Input::get('order_by', 0);
        if (!is_numeric($orderBy) || ($orderBy != Constants::ORDER_BY_ASC && $orderBy != Constants::ORDER_BY_DESC)) {
            return ErrorCode::errorParam('order_by');
        } else {
            $orderBy = $orderBy == 0 ? 'asc' : 'desc';
        }
        if ($per_page > 100 || $per_page < 1 || !is_numeric($per_page)) {
            $per_page = Constants::PER_PAGE_DEFAULT;
        }
        if ($page < 1 || !is_numeric($page)) {
            $page = Constants::PAGE_DEFAULT;
        }
        if (!is_numeric($startTime) || $startTime < 0) {
            return ErrorCode::errorParam('start_time');
        }

        if (!is_numeric($endTime) || $endTime < 0) {
            $endTime = time() + 365 * 24 * 3600;
        }

        if (is_null($tripState)) {
            $tripState = [
                Order::TRIP_STATE_WAIT_TO_DEPARTURE,
                Order::TRIP_STATE_DRIVE_TO_PICK_UP,
                Order::TRIP_STATE_WAITING_CUSTOMER,
                Order::TRIP_STATE_GO_TO_DROP_OFF,
                Order::TRIP_STATE_WAITING_DRIVER_DETERMINE,
                Order::TRIP_STATE_WAITING_TO_SETTLE,
                Order::TRIP_STATE_SETTLING,
                Order::TRIP_STATE_SETTLE_DONE
            ];
        } else {
            $tripState = explode(',', $tripState);
        }
        $orderState = [
            Order::ORDER_STATE_ADMIN_CANCEL,
            Order::ORDER_STATE_SUPER_ADMIN_CANCEL,
            Order::ORDER_STATE_PASSENGER_CANCEL,
            Order::ORDER_STATE_TIMES_UP_CANCEL,
            Order::ORDER_STATE_WAIT_DETERMINE,
        ];

        $skip = $per_page * ($page - 1);
        $bookingCount = Booking::leftjoin('orders', 'orders.booking_id', '=', 'bookings.id')
            ->leftjoin('booking_transaction_histories as bth', 'bth.booking_id', '=', 'bookings.id')
            ->leftjoin('customers', 'customers.id', '=', 'bookings.customer_id')
            ->leftjoin('users', 'users.id', '=', 'customers.user_id')
            ->where('bookings.exe_com_id', $company_id)
            ->where('bookings.driver_id', $driver_id)
            ->whereRaw("unix_timestamp(bookings.appointed_at)>={$startTime}")
            ->whereRaw("unix_timestamp(bookings.appointed_at)<{$endTime}")
            ->whereNotIn('orders.order_state', $orderState)
            ->whereIn('orders.trip_state', $tripState)
            ->where('bookings.reject', Booking::REJECT_TYPE_NORMAL)
            ->where(function ($query) use ($search) {
                $query->where('bookings.d_address', 'like', "%" . $search . "%")
                    ->orWhere('bookings.a_address', 'like', "%" . $search . "%")
                    ->orWhere('users.first_name', 'like', "%" . $search . "%")
                    ->orWhere('users.last_name', 'like', "%" . $search . "%")
                    ->orWhere('users.mobile', 'like', "%" . $search . "%")
                    ->orWhere('users.email', 'like', "%" . $search . "%");

            })
            ->count();
        $bookings = Booking::leftjoin('orders', 'orders.booking_id', '=', 'bookings.id')
            ->leftjoin("booking_airlines", "bookings.id", "=", "booking_airlines.booking_id")
            ->leftjoin('booking_transaction_histories as bth', 'bth.booking_id', '=', 'bookings.id')
            ->leftjoin('customers', 'customers.id', '=', 'bookings.customer_id')
            ->leftjoin('users', 'users.id', '=', 'customers.user_id')
            ->leftjoin('companies as ec', "ec.id", "=", "bookings.exe_com_id")
            ->leftjoin('companies as oc', "oc.id", "=", "bookings.company_id")
            ->leftjoin("company_settings", "ec.id", "=", "company_settings.company_id")
            ->where('bookings.exe_com_id', $company_id)
            ->where('bookings.driver_id', $driver_id)
            ->whereRaw("unix_timestamp(bookings.appointed_at)>={$startTime}")
            ->whereRaw("unix_timestamp(bookings.appointed_at)<{$endTime}")
            ->whereNotIn('orders.order_state', $orderState)
            ->whereIn('orders.trip_state', $tripState)
            ->where('bookings.reject', Booking::REJECT_TYPE_NORMAL)
            ->where(function ($query) use ($search) {
                $query->where('bookings.d_address', 'like', "%" . $search . "%")
                    ->orWhere('bookings.a_address', 'like', "%" . $search . "%")
                    ->orWhere('users.first_name', 'like', "%" . $search . "%")
                    ->orWhere('users.last_name', 'like', "%" . $search . "%")
                    ->orWhere('users.mobile', 'like', "%" . $search . "%")
                    ->orWhere('users.email', 'like', "%" . $search . "%");

            })
            ->select(
                'bookings.company_id',
                'bookings.car_data',
                'bookings.driver_data',
                'bookings.customer_data',
                'bookings.option_data',
                'bookings.offer_data',
                DB::raw('
                    CASE WHEN orders.trip_state >= ' . Order::TRIP_STATE_WAITING_TO_SETTLE . ' THEN
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
                'bookings.unit',
                'bookings.estimate_time',
                'bookings.estimate_distance',
                'bookings.message',
                'bookings.passenger_count',
                'bookings.bags_count',
                'bth.ccy',
                'bookings.passenger_names',
                'booking_airlines.a_airline',
                'booking_airlines.d_airline',
                'booking_airlines.a_flight',
                'booking_airlines.d_flight',
                'oc.name as own_company_name',
                'oc.id as own_company_id',
                'oc.timezone as company_timezone',
                DB::raw(UrlSpell::getUrlSpell()->getCompaniesLogoByNameInDB('own_company_logo', 'oc')),
                'ec.name as exe_company_name',
                'ec.id as exe_company_id',
                'company_settings.hide_driver_fee',
                DB::raw(UrlSpell::getUrlSpell()->getCompaniesLogoByNameInDB('exe_company_logo', 'ec'))
            )
            ->skip($skip)
            ->take($per_page)
            ->orderBy('bookings.appointed_at', $orderBy)
            ->get();

        if ($bookingCount == 0) {
            return ErrorCode::successEmptyResult('');
        } else {
            foreach ($bookings as $booking) {
                $customer = json_decode($booking->customer_data);
                $booking->c_first_name = $customer->first_name;
                $booking->c_last_name = $customer->last_name;
                $booking->c_gender = $customer->gender;
                $booking->c_mobile = $customer->mobile;
                $booking->c_email = $customer->email;
                $booking->c_avatar_url = $customer->avatar_url;

                $date = new \DateTime("@{$booking->appointed_at}");
                
                /*modifed by Pham 3/17/2018
                $company_timezone = $booking->company_timezone;
                $company_timezone = new \DateTimeZone($company_timezone);
                $date->setTimezone($company_timezone);*/
                
                $date->setTimezone(new \DateTimeZone($company_timezone));
                
                $booking->temp_appointed_at = $date->format("Y-m-d H:ia");
            }

            $result = ['total' => $bookingCount, 'bookings' => $bookings];
            return ErrorCode::success($result, false);
        }

    }

    public function driversGetTripBookings(Request $request)
    {
        $bookingId = Input::get("booking_id",null);
        $trip_state = Input::get("trip_state",null);
        $company_id = $request->user->company_id;
        $driver_id = $request->user->driver->id;
        $tripState = [
            Order::TRIP_STATE_DRIVE_TO_PICK_UP,
            Order::TRIP_STATE_WAITING_CUSTOMER,
            Order::TRIP_STATE_GO_TO_DROP_OFF,
            Order::TRIP_STATE_WAITING_DRIVER_DETERMINE,
        ];
        $orderState = [
            Order::ORDER_STATE_ADMIN_CANCEL,
            Order::ORDER_STATE_SUPER_ADMIN_CANCEL,
            Order::ORDER_STATE_PASSENGER_CANCEL,
            Order::ORDER_STATE_TIMES_UP_CANCEL,
            Order::ORDER_STATE_WAIT_DETERMINE,
        ];
        $booking = Booking::leftjoin('orders', 'orders.booking_id', '=', 'bookings.id')
            ->leftjoin("booking_airlines", "bookings.id", "=", "booking_airlines.booking_id")
            ->leftjoin('booking_transaction_histories as bth', 'bth.booking_id', '=', 'bookings.id')
            ->leftjoin('customers', 'customers.id', '=', 'bookings.customer_id')
            ->leftjoin('users', 'users.id', '=', 'customers.user_id')
            ->leftjoin('companies as ec', "ec.id", "=", "bookings.exe_com_id")
            ->leftjoin('companies as oc', "oc.id", "=", "bookings.company_id")
            ->leftjoin("company_settings", "ec.id", "=", "company_settings.company_id")
            ->where(function ($query) use ($bookingId){
                if(!is_null($bookingId)){
                    $query->where("bookings.id",$bookingId);
                }
            })
            ->where('bookings.exe_com_id', $company_id)
            ->where('bookings.driver_id', $driver_id)
            ->whereNotIn('orders.order_state', $orderState)
            ->where(function ($query) use ($tripState ,$trip_state){
                if(is_null($trip_state)){
                    $query->whereIn('orders.trip_state', $tripState);
                }else{
                    $query->where('orders.trip_state', $trip_state);
                }
            })
            ->where('bookings.reject', Booking::REJECT_TYPE_NORMAL)
            ->select(
                'bookings.company_id',
                'bookings.car_data',
                'bookings.driver_data',
                'bookings.customer_data',
                'bookings.option_data',
                'bookings.offer_data',
                DB::raw('
                    CASE WHEN orders.trip_state >= ' . Order::TRIP_STATE_WAITING_TO_SETTLE . ' THEN
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
                'bookings.unit',
                'bookings.estimate_time',
                'bookings.estimate_distance',
                'bookings.message',
                'bookings.passenger_count',
                'bookings.bags_count',
                'bth.ccy',
                'bookings.passenger_names',
                'booking_airlines.a_airline',
                'booking_airlines.d_airline',
                'booking_airlines.a_flight',
                'booking_airlines.d_flight',
                'oc.name as own_company_name',
                'oc.id as own_company_id',
                DB::raw(UrlSpell::getUrlSpell()->getCompaniesLogoByNameInDB('own_company_logo', 'oc')),
                'ec.name as exe_company_name',
                'ec.id as exe_company_id',
                'company_settings.hide_driver_fee',
                DB::raw(UrlSpell::getUrlSpell()->getCompaniesLogoByNameInDB('exe_company_logo', 'ec'))
            )
            ->orderBy('bookings.appointed_at', "desc")
            ->first();

        if (empty($booking)) {
            return ErrorCode::successEmptyResult('');
        } else {
            $customer = json_decode($booking->customer_data);
            $booking->c_first_name = $customer->first_name;
            $booking->c_last_name = $customer->last_name;
            $booking->c_gender = $customer->gender;
            $booking->c_mobile = $customer->mobile;
            $booking->c_email = $customer->email;
            $booking->c_avatar_url = $customer->avatar_url;

            return ErrorCode::success($booking);
        }

    }

    public function driverGetBookingDetail(Request $request, $booking_id)
    {
        $driver_id = $request->user->driver->id;
        $company_id = $request->user->driver->id;
        $booking = Booking::getBookingDetail($booking_id, $company_id, $driver_id);
        if (empty($booking)) {
            return ErrorCode::errorNotExist('booking');
        }
        $customer = json_decode($booking->customer_data);
        $booking->c_first_name = $customer->first_name;
        $booking->c_last_name = $customer->last_name;
        $booking->c_gender = $customer->gender;
        $booking->c_mobile = $customer->mobile;
        $booking->c_email = $customer->email;
        $booking->c_avatar_url = $customer->avatar_url;

        return ErrorCode::success($booking);
    }

    public function companiesGetBookingCounts(Request $request)
    {
        $company_id = $request->user->company_id;
        $start_time = Input::get('start_time', null);
        $end_time = Input::get('end_time', null);
        $timezone = Input::get('timezone', 'GMT');
        $search = Input::get('search', "%");
        $filter = Input::get('filter', Constants::BOOK_FILTER_ALL);
        $tripState = Input::get('trip_state', null);
        $orderState = Input::get('order_state', null);

        if (!is_numeric($start_time) || $start_time < 0) {
            return ErrorCode::errorParam('start time');
        }
        if (!is_numeric($end_time) || $end_time < 0) {
            return ErrorCode::errorParam('end time');
        }
        $count = floor(($end_time - $start_time) / (24 * 3600));
        if ($count < 1) {
            return ErrorCode::errorParam('start time and end time');
        }

        try {
            $timezone = new \DateTimeZone($timezone);
        } catch (\Exception $ex) {
            return ErrorCode::errorParam('timezone');
        }

        if(!is_null($start_time)) {

            $startDate = new \DateTime("@{$start_time}");
            $startDate->setTimezone($timezone);
            $startDateStr = "'" . $startDate->format('Y-m-d H:i:s') . "'";
        }
        else
            $startDateStr = $start_time;    

        if(!is_null($end_time))
        {
            $endDate = new \DateTime("@{$end_time}");
            $endDate->setTimezone($timezone);
            $endDateStr = "'" . $endDate->format('Y-m-d H:i:s') . "'";
        }
        else
            $endDateStr = $end_time;
    

        if (empty($tripState)) {
            $tripState = [
                Order::TRIP_STATE_WAIT_TO_DEPARTURE,
                Order::TRIP_STATE_DRIVE_TO_PICK_UP,
                Order::TRIP_STATE_WAITING_CUSTOMER,
                Order::TRIP_STATE_GO_TO_DROP_OFF,
                Order::TRIP_STATE_WAITING_DRIVER_DETERMINE,
                Order::TRIP_STATE_WAITING_TO_SETTLE,
                Order::TRIP_STATE_SETTLING,
                Order::TRIP_STATE_SETTLE_DONE
            ];
        } else {
            $tripState = explode(',', $tripState);
        }

        if (empty($orderState)) {
            $orderState = [
                Order::ORDER_STATE_BOOKING,
                Order::ORDER_STATE_RUN,
                Order::ORDER_STATE_DRIVER_UNRUN,
                Order::ORDER_STATE_SETTLE_ERROR,
                Order::ORDER_STATE_DONE,
                Order::ORDER_STATE_ADMIN_CANCEL,
                Order::ORDER_STATE_SUPER_ADMIN_CANCEL,
                Order::ORDER_STATE_PASSENGER_CANCEL,
                Order::ORDER_STATE_TIMES_UP_CANCEL,
                Order::ORDER_STATE_WAIT_DETERMINE,
            ];
        } else {
            $orderState = explode(',', $orderState);
        }
        $sql = '';
        $tempStart = $start_time;

        while ($tempStart < $end_time) {
            $date = new \DateTime("@{$tempStart}");
            $date->setTimezone($timezone);
        $step_startDate = "'" . $date->format('Y-m-d H:i:s') . "'";
            $start = strtotime($date->format('Y-m-d H:i:s O'));
            date_add($date, date_interval_create_from_date_string("1 day"));
            $end = $tempStart = strtotime($date->format('Y-m-d H:i:s O'));
        $step_endDate = "'" . $date->format('Y-m-d H:i:s') . "'";
            if ($end > $end_time) {
                $end = $end_time;
            }

            $sql = $sql . "ifnull(sum(case when bookings.appointed_at_pickup>={$step_startDate} AND bookings.appointed_at_pickup< {$step_endDate} THEN
  1
  ELSE 0 end ),0)as '" . $start . "~" . $end . "',";
        };
        if (empty($sql)) {
            return ErrorCode::errorParam('start time and end time');
        }

        $sql = substr($sql, 0, -1);

        $result = Booking::leftjoin('orders', 'orders.booking_id', '=', 'bookings.id')
//            ->leftjoin('booking_transaction_histories as bth', 'bth.booking_id', '=', 'bookings.id')
            ->leftjoin('customers', 'customers.id', '=', 'bookings.customer_id')
            ->leftjoin('users', 'users.id', '=', 'customers.user_id')
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
            ->whereRaw("bookings.appointed_at_pickup>={$startDateStr}")
            ->whereRaw("bookings.appointed_at_pickup<{$endDateStr}")
            ->whereIn('orders.order_state', $orderState)
            ->whereIn('orders.trip_state', $tripState)
            ->where(function ($query) use ($search) {
                $query->where('bookings.d_address', 'like', "%" . $search . "%")
                    ->orWhere('bookings.a_address', 'like', "%" . $search . "%")
                    ->orWhere('users.first_name', 'like', "%" . $search . "%")
                    ->orWhere('users.last_name', 'like', "%" . $search . "%")
                    ->orWhere('users.mobile', 'like', "%" . $search . "%")
                    ->orWhere('users.email', 'like', "%" . $search . "%");

            })
            ->select(DB::raw($sql))
            ->first();

        $items = $result->toArray();
        $temp = array();
        foreach (array_keys($items) as $item) {
            $time = explode("~", $item);
            array_push($temp, ["start_time" => $time[0], "end_time" => $time[1], "counts" => $items[$item]]);
        }
        return ErrorCode::success($temp);
    }


    public function driversGetBookingsCounts(Request $request)
    {
        $company_id = $request->user->company_id;
        $start_time = Input::get('start_time', null);
        $end_time = Input::get('end_time', null);
        $timezone = Input::get('timezone', 'GMT');
        $search = Input::get('search', "%");
        $tripState = Input::get('trip_state', null);
        $orderState = Input::get('order_state', null);
        if (!is_numeric($start_time) || $start_time < 0) {
            return ErrorCode::errorParam('start_time');
        }
        try {
            $timezone = new \DateTimeZone($timezone);
        } catch (\Exception $ex) {
            return ErrorCode::errorParam('timezone');
        }
        if (!is_numeric($end_time) || $end_time < 0) {
            return ErrorCode::errorParam('start_time');
        }
        $count = floor(($end_time - $start_time) / (24 * 3600));
        if ($count < 1) {
            return ErrorCode::errorParam('start time and end time');
        }
        if (empty($tripState)) {
            $tripState = [
                Order::TRIP_STATE_WAIT_TO_DEPARTURE,
                Order::TRIP_STATE_DRIVE_TO_PICK_UP,
                Order::TRIP_STATE_WAITING_CUSTOMER,
                Order::TRIP_STATE_GO_TO_DROP_OFF,
                Order::TRIP_STATE_WAITING_DRIVER_DETERMINE,
                Order::TRIP_STATE_WAITING_TO_SETTLE,
                Order::TRIP_STATE_SETTLING,
                Order::TRIP_STATE_SETTLE_DONE
            ];
        } else {
            $tripState = explode(',', $tripState);
        }

        if (empty($orderState)) {
            $orderState = [
                Order::ORDER_STATE_BOOKING,
                Order::ORDER_STATE_RUN,
                Order::ORDER_STATE_DRIVER_UNRUN,
                Order::ORDER_STATE_SETTLE_ERROR,
                Order::ORDER_STATE_DONE,
                Order::ORDER_STATE_ADMIN_CANCEL,
                Order::ORDER_STATE_SUPER_ADMIN_CANCEL,
                Order::ORDER_STATE_PASSENGER_CANCEL,
                Order::ORDER_STATE_TIMES_UP_CANCEL,
                Order::ORDER_STATE_WAIT_DETERMINE,
            ];
        } else {
            $orderState = explode(',', $orderState);
        }

        $sql = '';
        $tempStart = $start_time;
        while ($tempStart < $end_time) {
            $date = new \DateTime("@{$tempStart}");
            $date->setTimezone($timezone);
            $start = strtotime($date->format('Y-m-d H:i:s O'));
            date_add($date, date_interval_create_from_date_string("1 day"));
            $end = $tempStart = strtotime($date->format('Y-m-d H:i:s O'));
            if ($end > $end_time) {
                $end = $end_time;
            }

            $sql = $sql . "ifnull(sum(case when unix_timestamp(bookings.appointed_at)>=" . $start . " AND unix_timestamp(bookings.appointed_at)<" . $end . " THEN
  1
  ELSE 0 end ),0)as '" . $start . "~" . $end . "',";
        }
        if (empty($sql)) {
            return ErrorCode::errorParam('start time and end time');
        }

        $sql = substr($sql, 0, -1);

        $result = Booking::leftjoin('orders', 'orders.booking_id', '=', 'bookings.id')
//            ->leftjoin('booking_transaction_histories as bth', 'bth.booking_id', '=', 'bookings.id')
            ->leftjoin('customers', 'customers.id', '=', 'bookings.customer_id')
            ->leftjoin('users', 'users.id', '=', 'customers.user_id')
            ->where('bookings.exe_com_id', $company_id)
            ->whereRaw("unix_timestamp(bookings.appointed_at)>={$start_time}")
            ->whereRaw("unix_timestamp(bookings.appointed_at)<{$end_time}")
            ->whereIn('orders.order_state', $orderState)
            ->whereIn('orders.trip_state', $tripState)
            ->where(function ($query) use ($search) {
                $query->where('bookings.d_address', 'like', "%" . $search . "%")
                    ->orWhere('bookings.a_address', 'like', "%" . $search . "%")
                    ->orWhere('users.first_name', 'like', "%" . $search . "%")
                    ->orWhere('users.last_name', 'like', "%" . $search . "%")
                    ->orWhere('users.mobile', 'like', "%" . $search . "%")
                    ->orWhere('users.email', 'like', "%" . $search . "%");
            })
            ->select(DB::raw($sql))
            ->first();
        $items = $result->toArray();
        $temp = array();
        foreach (array_keys($items) as $item) {
            $time = explode("~", $item);
            array_push($temp, ["start_time" => $time[0], "end_time" => $time[1], "counts" => $items[$item]]);
        }
        return ErrorCode::success($temp);
    }

    public function sendCustomersBooking(Request $request, $booking_id)
    {
        $company_id = $request->user->company_id;

        $bookingCount = Booking::where('id', $booking_id)
            ->where('company_id', $company_id)
            ->count();

        if ($bookingCount == 0) {
            return ErrorCode::errorNotExist('booking');
        } else {
            $sender = (new SendEmailCustomerBookingJob($booking_id));
            $this->dispatch($sender);
            return ErrorCode::success('success');
        }
    }

    public function billBSendBack(Request $request, $booking_id)
    {
        $admin_id = $request->user->admin->id;
        $company_id = $request->user->company_id;
        $booking = Booking::where('exe_com_id', $company_id)
            ->where('id', $booking_id)
            ->where('company_id', '!=', $company_id)
            ->where('reject', Booking::REJECT_TYPE_NORMAL)
            ->first();
        if (empty($booking)) {
            return ErrorCode::errorAdminUnauthorizedOperation();
        }

        $order = Order::where('booking_id', $booking_id)->first();
        if ($order->order_state != Order::ORDER_STATE_BOOKING &&
            $order->trip_state != Order::TRIP_STATE_WAIT_TO_DEPARTURE
        ) {
            return ErrorCode::errorOrderTripStart();
        }


        BookingChangeHistory::create(
            [
                "company_id" => $company_id,
                "admin_id" => $admin_id,
                "booking_id" => $booking_id,
                "booking_info" => json_encode($booking),
                "action_type" => BookingChangeHistory::ACTION_TYPE_WITHDRAW
            ]
        );

        $booking->reject = Booking::REJECT_TYPE_REJECT;
        $booking->save();
        CalendarEvent::where("creator_id", $booking_id)
            ->where("creator_type", CalendarEvent::CREATOR_TYPE_BOOKING)
            ->update(["enable" => CalendarEvent::EVENT_DISABLE]);
        $this->dispatch(new SendEmailAdminBookingSendBackJob($booking_id));
        return ErrorCode::success('success');
    }
}
