<?php

namespace App\Http\Controllers\v1;

use App\ErrorCode;
use App\Method\OrderStateAlgorithm;
use App\Method\PaymentAlgorithm;
use App\Model\Booking;
use App\Model\Company;
use App\Model\CompanyPayMethod;
use App\Model\CompanySetting;
use App\Model\Customer;
use App\Method\BookingMatchAlgorithm;
use App\Method\OfferMatchAlgorithm;
use App\Model\Offer;
use App\Model\Coupon;
use App\Model\Onetime_couponHistory;
use Faker\Provider\Uuid;
use \Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Input;

class TripController extends Controller
{
    /*-------------------------check offers--------------------------------*/
    public function checkOfferForTrip($company_id)
    {
        $company = Company::where('id', $company_id)->first();
        if (empty($company)) {
            return ErrorCode::errorNotExist('company');
        }
        $type = Input::get('type', null);
        $d_lat = Input::get('d_lat', null);
        $d_lng = Input::get('d_lng', null);
        $a_lat = Input::get('a_lat', null);
        $a_lng = Input::get('a_lng', null);
        $estimate_distance = Input::get('estimate_distance', null);
        $unit = Input::get('unit', CompanySetting::UNIT_MI);
        $estimate_duration = Input::get('estimate_duration', null);
        $appointed_time = Input::get('appointed_time', null);
        $car_category_id = Input::get('car_category', 0);
        $d_is_airport = Input::get('d_is_airport', 0);
        $a_is_airport = Input::get('a_is_airport', 0);
        $token = Input::get("token", Uuid::uuid());
        if (is_null($type) ||
            is_null($appointed_time) ||
            is_null($d_lat) ||
            is_null($d_lng) ||
            is_null($car_category_id) ||
            is_null($estimate_duration) ||
            is_null($unit)
        ) {
            return ErrorCode::errorMissingParam();
        }

        if (!is_numeric($unit) ||
            ($unit != CompanySetting::UNIT_MI &&
                $unit != CompanySetting::UNIT_KM)) {
            return ErrorCode::errorParam("unit");
        }
        if (!is_numeric($d_is_airport) || ($d_is_airport != Offer::IS_AIRPORT && $d_is_airport != Offer::NOT_AIRPORT)) {
            return ErrorCode::errorParam('d_is_airport');
        }
        if (!is_numeric($a_is_airport) || ($a_is_airport != Offer::IS_AIRPORT && $a_is_airport != Offer::NOT_AIRPORT)) {
            return ErrorCode::errorParam('a_is_airport');
        }
        if (!is_numeric($appointed_time) || $appointed_time < time()) {
            return ErrorCode::errorParam('appointed_time');
        }
        if (!is_numeric($d_lat) || $d_lat > 90 || $d_lat < -90) {
            return ErrorCode::errorParam('d_lat');
        }
        if (!is_numeric($d_lng) || $d_lng > 180 || $d_lng < -180) {
            return ErrorCode::errorParam('d_lat');
        }
        $matchAlgorithm = new OfferMatchAlgorithm();
        if ($type == Booking::CHECK_TYPE_DISTANCE) {
            if (is_null($a_lat) ||
                is_null($a_lng) ||
                is_null($estimate_distance)
            ) {
                return ErrorCode::errorMissingParam();
            }
            if (!is_numeric($a_lat) || $a_lat > 90 || $a_lat < -90) {
                return ErrorCode::errorParam('a_lat');
            }
            if (!is_numeric($a_lng) || $a_lng > 180 || $a_lng < -180) {
                return ErrorCode::errorParam('a_lat');
            }

            try {
                $offersResult = $matchAlgorithm->offerP2PSearch($company_id, $unit, $d_lat, $d_lng, $a_lat, $a_lng,
                    $estimate_distance, $estimate_duration, $car_category_id, $appointed_time, $token
                );
            } catch (\Exception $ex) {
                return $ex->getMessage();
            }
        } elseif ($type == Offer::CHECK_TYPE_HOURLY) {
            try {
                $offersResult = $matchAlgorithm->offerHourlySearch($company_id, $unit, $d_lat, $d_lng,
                    $estimate_duration, $car_category_id, $appointed_time, $token);
            } catch (\Exception $ex) {
                return $ex->getMessage();
            }
        } else {
            return ErrorCode::errorParam("unknown type");
        }
        $offers = $offersResult['offer'];
        if (count($offers) == 0) {
            $offerError = $offersResult['offerError'];
            if ($offerError['car']) {
                return ErrorCode::errorOffersHasNoDrivers();
            } else {
                return ErrorCode::errorOffersHasNoCars();
            }
        } else {
            // æš‚æ—¶åŽ»æŽ‰é™åˆ¶
            // TEMP ä¸´æ—¶ç®—æ³•
//            if (count($offers) > 4) {
//                $offers = array_slice($offers, 0, 4);
//            }
            return ErrorCode::success($offers);
        }
    }

    public function checkCustomerQuote(Request $request)
    {
        $appointed_time = Input::get("appointed_time", null);
        $estimate_duration = Input::get("estimate_duration", null);
        $a_lat = $d_lat = Input::get("lat", null);
        $a_lng = $d_lng = Input::get("lng", null);
        $delay_time = ($appointed_time - time()) / 60;
        $token = Input::get("token", null);
        if (is_null($appointed_time) ||
            is_null($estimate_duration) ||
            is_null($a_lng) ||
            is_null($a_lat)
        ) {
            return ErrorCode::errorMissingParam();
        }

        if ($delay_time < 0) {
            return ErrorCode::errorParam('appointed_time');
        }

        $company_id = $request->user->company_id;
        try {
            return DB::transaction(function () use (
                $company_id, $token, $appointed_time,
                $estimate_duration, $delay_time, $a_lat, $a_lng, $d_lat, $d_lng
            ) {
                $offerMatchAlgorithm = new OfferMatchAlgorithm();
                return $offerMatchAlgorithm->checkCustomerQuote(
                    $company_id, $token, $appointed_time,
                    $estimate_duration, $delay_time, $a_lat, $a_lng, $a_lat, $a_lng);
            });
        } catch (\Exception $ex) {
            return $ex->getMessage();
        }
    }
    /*-------------------------add bookings--------------------------------*/

    /**
     * æ·»åŠ booking
     *      0,éªŒè¯å‚æ•°æ˜¯å¦æ­£ç¡®
     *      1.éªŒè¯offer,driver,caræ˜¯å¦å¯ç”¨,
     *      2.éªŒè¯ç»ˆç«¯è®¡ç®—çš„è´¹ç”¨æ˜¯å¦æ­£ç¡®,
     *      3.ä»˜è´¹
     *      4.ç”Ÿæˆbooking,
     *      5.è¿”å›žç»“æžœ
     * @param $request
     * @return string
     */
    public function adminAddBooking(Request $request)
    {
        $customer_id = Input::get('customer_id', null);
        $param = Input::get("param", null);
        $param = json_decode($param, true);
        if (is_null($customer_id) || is_null($param)) {
            return ErrorCode::errorMissingParam();
        }
        $customer = Customer::where('customers.id', $customer_id)
            ->leftjoin('users', 'users.id', '=', 'customers.user_id')
            ->select('users.company_id', 'customers.id as customer_id')
            ->first();
        if (empty($customer)) {
            return ErrorCode::errorParam('customer_id');
        }

        $company_id = $request->user->company_id;
        $token = $request->user->token;
        if ($customer->company_id != $company_id) {
            return ErrorCode::errorAdminUnauthorizedOperation();
        }
        return (new BookingMatchAlgorithm())->addBookings($company_id, $customer_id, $param, $customer, $token);
    }

    public function customerAddBooking(Request $request)
    {
        $customer = $request->user->customer;
        $company_id = $request->user->company_id;
        $token = $request->user->token;
        $customer->customer_id = $customer->id;
        $param = Input::get("param", null);
        $param = json_decode($param, true);
        if (empty($customer->id) || empty($param)) {
            return ErrorCode::errorMissingParam();
        } else {
        }
        return (new BookingMatchAlgorithm())->addBookings($company_id, $customer->id, $param, $customer, $token);
    }


    public function addCustomerQuote(Request $request)
    {
        $customer_id = Input::get('customer_id', null);
        $param = Input::get("param", null);
        $customDetermine = Input::get("determine", null);
        $param = json_decode($param, true);
        if (is_null($customer_id) || is_null($param) || is_null($customDetermine)) {
            return ErrorCode::errorMissingParam();
        } else {
        }
        $customer = Customer::where('customers.id', $customer_id)
            ->leftjoin('users', 'users.id', '=', 'customers.user_id')
            ->select('users.company_id', 'customers.id as customer_id')
            ->first();
        if (empty($customer)) {
            return ErrorCode::errorParam('customer_id');
        }

        $company_id = $request->user->company_id;
        $token = $request->user->token;
        if ($customer->company_id != $company_id) {
            return ErrorCode::errorAdminUnauthorizedOperation();
        } else {
        }

        if (!is_numeric($customDetermine) || ($customDetermine != 0 && $customDetermine != 1)) {
            return ErrorCode::errorParam('determine');
        }

        try {
            $booking = new BookingMatchAlgorithm();
            return $booking->addCustomQuote($company_id, $customer_id, $param, $customDetermine, $token);
        } catch (\Exception $ex) {
            return $ex->getMessage();
        }
    }

    public function adminChangeBookingStat(Request $request)
    {
        $companyId = $request->user->company_id;
        $adminId = $request->user->admin->id;
        $bookingId = Input::get("booking_id", null);
        $state = Input::get('state', null);
        if (is_null($bookingId) || is_null($state)) {
            return ErrorCode::errorMissingParam();
        }

        if (!is_numeric($state) ||
            ($state != Order::TRIP_STATE_GO_TO_DROP_OFF &&
                $state != Order::TRIP_STATE_WAITING_DRIVER_DETERMINE)
        ) {
            return ErrorCode::errorParam('state');
        }

        return DB::transaction(function () use ($companyId, $bookingId, $state, $adminId) {
            return OrderStateAlgorithm::getOrderState()->adminChangeOrderState($companyId, $bookingId, $adminId, $state);
        });
    }

    public function checkCompanyCouponCode($id, $code)
    {

        $car_companyId = Input::get('car_companyId', null);
        $appointedTime = Input::get('appointedTime', null);
        $customer_id = Input::get('customer_id', null);

        $user_id = Customer::where('id', $customer_id)->value('user_id');
        
        $payInfo = CompanyPayMethod::where('company_id', $id)
            ->where("active", CompanyPayMethod::ACTIVE)
            ->first();



        if (empty($payInfo)) {
            return ErrorCode::errorCouponCode();
        }
        //$couponResult = PaymentAlgorithm::getPayment()->getCouponInfo($payInfo, $code);
        $couponResult = Coupon::where([['code', $code], ['company_id', $id], ['turn_state', 1]])->whereNull('deleted_at')->get();

        if (count($couponResult) == 0) {
            return ErrorCode::errorCouponCode();
        }

        if($couponResult[0]->is_onetime == 1) {
            $count = Onetime_couponHistory::where([['coupon_code', $code], ['user_id', $user_id], ['company_id', $id]])->count();
            if($count > 0) {
                return ErrorCode::errorAlreadyUsed('Coupon code is');
            }
        }
   
        if($couponResult[0]->is_permanent == 0){

            if($couponResult[0]->end_date == '' && $couponResult[0]->starting_date == '') {
                return ErrorCode::errorNotExist('coupon');
            }

            $coupon_expirationDate = new \DateTime($couponResult[0]->end_date);
            $coupon_startingDate = new \DateTime($couponResult[0]->starting_date);


            if($coupon_expirationDate < new \DateTime($appointedTime) || $coupon_startingDate > new \DateTime($appointedTime))
                return ErrorCode::errorNotExist('coupon');
        }
        
        if (!is_null($couponResult[0])) {
            if(!is_null($car_companyId)){
                if($couponResult[0]->company_id == $car_companyId)
                    return ErrorCode::success($couponResult);
                else
                    return ErrorCode::errorNotExist('coupon');
            }
            else {
                return ErrorCode::success($couponResult);
            }
            
        }else {
            return ErrorCode::errorNotExist('coupon');
        }
        
    }

}