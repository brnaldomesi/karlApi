<?php
/**
 * Created by PhpStorm.
 * User: lqh
 * Date: 2017/3/4
 * Time: 下午11:38
 */

namespace App\Http\Controllers\v1;


use App\ErrorCode;
use App\Method\AirlineAndFlightMethod;
use App\Model\Booking;
use App\Model\BookingAirline;
use Curl\Curl;
use Illuminate\Support\Facades\Input;

class AirlineController
{
    public function getAirlineAndFlightByLatLng()
    {

        $lat  = Input::get("lat",null);
        $lng  = Input::get("lng",null);
        $time = Input::get("time",null);
        $type = Input::get("type",null);


        if(!is_numeric($lat) || $lat > 90  || $lat<-90 ){
            return ErrorCode::errorParam("lat");
        }
        if(!is_numeric($lng) || $lng > 180 || $lng < -180){
           return ErrorCode::errorParam("lng");
        }
        if(!is_numeric($type) ||
            ( $type!= AirlineAndFlightMethod::ARRIVE_PORT &&
              $type!= AirlineAndFlightMethod::DEPART_PORT)){
            return ErrorCode::errorParam("type");
        }
        if(!is_numeric($time) || $time<0){
            return ErrorCode::errorParam("$time");
        }
        $flights = AirlineAndFlightMethod::getAirlineAndFlights($lat,$lng,$time,$type);
        if(is_null($flights)){
            return ErrorCode::successEmptyResult('has no flights.');
        }else{
            return ErrorCode::success($flights);
        }
    }

    public function getAirlineArriveStates($bookingId)
    {
        $airline = Booking::leftjoin("booking_airlines","bookings.id","=","booking_airlines.booking_id")
            ->where("bookings.id",$bookingId)
            ->select(
                \DB::raw("unix_timestamp(bookings.appointed_at) as time"),
                "booking_airlines.d_airline",
                "booking_airlines.d_flight"
            )
            ->first();
        if(empty($airline)||empty($airline->d_airline)||empty($airline->d_flight)){
            return ErrorCode::errorAirline();
        }

        try{
            return ErrorCode::success(AirlineAndFlightMethod::getFlightState(
                $airline->time,
                $airline->d_airline,
                $airline->d_flight
            ));
        }catch(\Exception $ex){
            return ErrorCode::errorAirline($ex->getMessage());
        }
    }
}