<?php
/**
 * Created by PhpStorm.
 * User: lqh
 * Date: 2016/11/17
 * Time: 下午12:01
 */

namespace App\Method;


use Curl\Curl;

class GeoLocationAlgorithm
{
    private static $_instance = null;

    /**
     * GeoLocationAlgorithm constructor.
     */
    private function __construct()
    {
    }

    /**
     * @return GeoLocationAlgorithm
     */
    public static function getInstance(){
        if(is_null(self::$_instance)){
            self::$_instance = new GeoLocationAlgorithm();
        }
        return self::$_instance;
    }

    /**
     * @param $lat
     * @param $lng
     * @param $timestamp
     * @return mixed
     */
    public function getLocationTime($lat,$lng,$timestamp){
        $curl = new Curl();
        $curl->get("https://maps.googleapis.com/maps/api/timezone/json?location={$lat},{$lng}&timestamp={$timestamp}&key={$_SERVER['TIMEZONE_KEY']}");
        $result = ($curl->response);
        return ($result);
    }

    public function checkAirport($placeId)
    {
        $curl = new Curl();

        $url= "https://maps.googleapis.com/maps/api/place/details/json?placeid={$placeId}&key={$_SERVER['GEOLOCAL_KEY']}";
        $result = json_decode($curl->get($url),true);
        foreach ($result['result']["types"] as $item) {
            if($item=="airport"){
                return true;
            }
        }
        return false;
    }

    public function checkAddress($address, $dontCheck=false)
    {
        if($dontCheck){
            return true;
        }else{
            $addJson = json_decode($address,true);
            return isset($addJson['place_id']);
        }
    }

    public function simplifyAddress($src_string)
    {
        $src_array = json_decode($src_string, true);
        if (!$src_array['address_components'] ||
            !$src_array['formatted_address'] ||
            !$src_array['geometry'] ||
            !$src_array['place_id']) {
            return null;
        }
        $dst_array = Array(
            'address_components' => $src_array['address_components'],
            'formatted_address' => $src_array['formatted_address'],
            'geometry' => $src_array['geometry'],
            'place_id' => $src_array['place_id']);
        if (isset($src_array['types'])) {
            $dst_array['types'] = $src_array['types'];
        }
        if (isset($src_array['name'])) {
            $dst_array['name'] = $src_array['name'];
        }
        if (isset($src_array['icon'])) {
            $dst_array['icon'] = $src_array['icon'];
        }
        if (isset($src_array['vicinity'])) {
            $dst_array['vicinity'] = $src_array['vicinity'];
        }
        $dst_string = json_encode((object)$dst_array);
        return $dst_string;
    }

}