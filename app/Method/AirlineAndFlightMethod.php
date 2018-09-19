<?php
/**
 * Created by PhpStorm.
 * User: lqh
 * Date: 2017/3/4
 * Time: 下午3:17
 */

namespace App\Method;


use App\Constants;
use Curl\Curl;

class AirlineAndFlightMethod
{
    const ARRIVE_PORT = 0;
    const DEPART_PORT = 1;
    public static function checkAirportCode($lat, $lng)
    {
        $curl = new Curl();
        $url = "https://api.flightstats.com/flex/airports/rest/v1/json/withinRadius/{$lng}/{$lat}/1?appId={$_SERVER['FLIGHT_ID']}&appKey={$_SERVER['FLIGHT_KEY']}";
        $response =$curl->get($url);
        $result['icao'] =$response->airports[0]->icao;
        $result['time_zone'] =$response->airports[0]->timeZoneRegionName;
        $result['off_set'] =$response->airports[0]->utcOffsetHours;
        return $result;
    }

    public static function getAirlineAndFlights($lat, $lng,$appointedTime,$dOrA)
    {
        try {
            $result = self::checkAirportCode($lat, $lng);
            $data = new \DateTime("@".$appointedTime);
            $data->setTimezone(new \DateTimeZone($result['time_zone']));
            $airline = [];
            $curl = new Curl();
            for($i=0;$i<6;$i++){
                $time = $data->format('Y/m/d/H');
                if($dOrA == self::ARRIVE_PORT){
                    $url = "https://api.flightstats.com/flex/schedules/rest/v1/json/to/{$result['icao']}/arriving/{$time}?appId={$_SERVER['FLIGHT_ID']}&appKey={$_SERVER['FLIGHT_KEY']}";
                }else{
                    $url = "https://api.flightstats.com/flex/schedules/rest/v1/json/from/{$result['icao']}/departing/{$time}?appId={$_SERVER['FLIGHT_ID']}&appKey={$_SERVER['FLIGHT_KEY']}";
                }
                $aAndf = $curl->get($url);
                try{
                    $airline = self::archiveAirLineAndFlight($aAndf,$airline,$dOrA,$result['off_set'],$appointedTime);
                }catch(\Exception $ex){
//                    Log::error("",$ex);
                }
                if($dOrA == self::ARRIVE_PORT){
                    date_add($data,date_interval_create_from_date_string("-1 hour"));
                }else{
                    date_add($data,date_interval_create_from_date_string("1 hour"));
                }
            }
            return array_values($airline);
        } catch (\Exception $ex) {
            return null;
        }
    }

    private static function archiveAirLineAndFlight($aAndF,$result,$dOrA,$timeOffset,$appointedTime)
    {
        foreach ($aAndF->appendix->airlines as $airline) {
            if(!isset($result[$airline->fs])){
                $result[$airline->fs] = self::stdClassToArray($airline);
            }
        }
        foreach ($aAndF->scheduledFlights as $item) {
            if(!isset($result[$item->carrierFsCode]['flights'])){
                $result[$item->carrierFsCode]['flights'] = [];
            }
            if($dOrA == self::ARRIVE_PORT){
                $time = strtotime($item->arrivalTime)-($timeOffset*Constants::HOUR_SECONDS);
                if($time > $appointedTime){
                    continue;
                }
            }else{
                $time = strtotime($item->departureTime)-($timeOffset*Constants::HOUR_SECONDS);
                if($time <= $appointedTime){
                    continue;
                }
            }
            $flight = self::stdClassToArray($item);
            unset($flight['serviceClasses']);
            unset($flight['codeshares']);
            unset($flight['operator']);
            array_push($result[$item->carrierFsCode]['flights'],$flight);
        }
        return $result;
    }

    private static function stdClassToArray($stdClass)
    {
        return json_decode(json_encode($stdClass),true);
    }


    public static function getFlightState($time,$airline,$flight)
    {
        $curl = new Curl();
        $date = new \DateTime("@".$time);
        $dateFormat = $date->format("Y/m/d");
        $url = "https://api.flightstats.com/flex/flightstatus/rest/v2/json/flight/tracks/{$airline}/{$flight}/arr/{$dateFormat}?appId={$_SERVER['FLIGHT_ID']}&appKey={$_SERVER['FLIGHT_KEY']}&utc=true&includeFlightPlan=false&maxPositions=2";
        $result = $curl->get($url);
        $result = self::stdClassToArray($result);
        if(isset($result['error']) &&
            isset($result['error']['httpStatusCode']) &&
        $result['error']['httpStatusCode'] ==400){
            throw new \Exception($result['error']['errorMessage']);
        }else{
            return $result;
        }
    }
}