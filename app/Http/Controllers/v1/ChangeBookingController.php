<?php

namespace App\Http\Controllers\v1;

use App\ErrorCode;
use App\Jobs\SendEmailCustomerBookingEditJob;
use App\Method\ChangeBookingMatchAlgorithm;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Input;

class ChangeBookingController extends Controller
{
    public function checkOfferMatchBooking(Request $request, $booking_id)
    {
        $type = Input::get('type', null);
        $d_lat = Input::get('d_lat', null);
        $d_lng = Input::get('d_lng', null);
        $d_is_airport = Input::get('d_is_airport', null);
        $a_lat = Input::get('a_lat', null);
        $a_lng = Input::get('a_lng', null);
        $a_is_airport = Input::get('a_is_airport', null);
        $estimate_distance = Input::get('estimate_distance', null);
        $estimate_duration = Input::get('estimate_duration', null);
        $appointed_time = Input::get('appointed_time', null);
        $pre_time = Input::get('pre_time', null);
        $token = Input::get("token", null);
        $company_id = $request->user->company_id;
        try{
            $offerResult =  \DB::transaction(function () use (
                $company_id,
                $type,
                $d_lat, $d_lng, $d_is_airport,
                $a_lat, $a_lng, $a_is_airport,
                $estimate_distance, $estimate_duration,
                $pre_time,
                $appointed_time,
                $token,
                $booking_id
            ) {
                return ChangeBookingMatchAlgorithm::getAlgorithm()->bookingsCheckOffers($company_id,
                    $type,
                    $d_lat, $d_lng, $d_is_airport,
                    $a_lat, $a_lng, $a_is_airport,
                    $estimate_distance, $estimate_duration,
                    $appointed_time,
                    $token,
                    $booking_id
                );
            });
            if (count($offerResult['offer']) == 0) {
                $offerError = $offerResult['offerError'];
                if ($offerError['car']) {
                    return ErrorCode::errorOffersHasNoDrivers();
                } else {
                    return ErrorCode::errorOffersHasNoCars();
                }
            } else {
                $offers = $offerResult['offer'];
                // 暂时去掉限制
                // TEMP 临时算法
//                if (count($offerResult['offer']) > 4) {
//                    $offers = array_slice($offers, 0, 4);
//                }
                return ErrorCode::success($offers);
            }
        }catch(\Exception $ex){
            return $ex->getMessage();
        }
    }

    public function updateBookingInfo(Request $request,$bookingId)
    {
        $token = $request->user->token;
        $adminId = $request->user->admin->id;
        $company_id = $request->user->company_id;
        $param = Input::get('param', null);
        try{
            $result = \DB::transaction(function() use ($token,$company_id,$param,$bookingId,$adminId){
                return ChangeBookingMatchAlgorithm::getAlgorithm()
                    ->changeBookings($company_id,$bookingId,$param,$token,$adminId);
            });
            $this->dispatch(new SendEmailCustomerBookingEditJob($bookingId));
            return ErrorCode::success($result);
        }catch(Exception $ex){
            \Log::error($ex);
            return $ex->getMessage();
        }
    }
}