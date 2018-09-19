<?php
/**
 * Created by PhpStorm.
 * User: liqihai
 * Date: 2016/9/27
 * Time: 上午9:53
 */

namespace App\Method;


use App\Constants;
use App\ErrorCode;
use App\Method\Money\Money;
use App\Model\BookingDayStatistic;
use App\Model\Company;
use App\Model\CompanySetting;
use Faker\Provider\cs_CZ\DateTime;

class MethodAlgorithm
{
    private static $AL = "ABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890,";
    private static $DICTIONARY = [
        "encode" => [
            "," => "d",
            "1" => "e",
            "2" => "f",
            "3" => "g",
            "4" => "a",
            "5" => "x",
            "6" => "w",
            "7" => "m",
            "8" => "n",
            "9" => "y",
            "0" => "o",
        ],
        "decode" => [
            "d" => ",",
            "e" => "1",
            "f" => "2",
            "g" => "3",
            "a" => "4",
            "x" => "5",
            "w" => "6",
            "m" => "7",
            "n" => "8",
            "y" => "9",
            "o" => "0",
        ]
    ];

    public static function checkFullGout()
    {
        $gout = $_SERVER['APP_GOUT'];
        $random = random_int(0, 100);
        return $random < $gout;
    }


    public static function formatTimestampToDate($time, $format = 'Y-m-d H:i:s')
    {
        $utcTime = new \DateTime("@" . $time);
        $utcTime->setTimezone(new \DateTimeZone('UTC'));
        return $utcTime->format($format);
    }


    public static function getRandomPassword($length = 8)
    {
        return str_random($length);
    }


    public static function decodeString($string)
    {
        /**
         * 1.首尾对调
         * 2.小写
         * 3.解密
         */
        $string = strrev($string);
        $string = strtolower($string);
        $temp = $string;
        foreach (count_chars($string, 1) as $i => $val) {
            $temp = str_replace(chr($i), self::$DICTIONARY["decode"][chr($i)], $temp);
        }

        return $temp;
    }

    public static function encodeString($string)
    {
        /**
         * 1.加密
         * 2.首尾对调
         * 3.随机字符串大小
         */
        $temp = $string;
        foreach (count_chars($string, 1) as $i => $val) {
            $temp = str_replace(chr($i), self::$DICTIONARY["encode"][chr($i)], $temp);
        }
        $string = strrev($temp);
        return $string;
    }

    /**
     * @param $time
     * @return int|string
     */
    public static function formatTime($time)
    {
        if ($time <= 1) {
            return "1 min";
        } else if ($time > 1 && $time <= 60) {
            return $time . " min";
        } else {
            if ($time % 60 == 0) {
                return floor($time / 60) . ":00 hrs";
            } else if ($time % 60 > 0 && $time % 60 < 10) {
                return floor($time / 60) . ":0" . round($time % 60) . " hrs";
            } else {
                return floor($time / 60) . ":" . round($time % 60) . " hrs";
            }
        }
    }

    /**
     * @param $prices array
     * @return array
     * @throws \Exception
     */
    public static function sortPrices($prices)
    {
        if (count($prices) == 1) {
            return $prices;
        }
        $start = [];
        $end = [];
        for ($i = 0; $i < count($prices); $i++) {
            $price = $prices[$i];
            if (!isset($price['invl_start']) || !isset($price['invl_end']) || !isset($price['price'])) {
                throw new \Exception(ErrorCode::errorParam('prices'));
            }
            if (!is_numeric($price['invl_start']) || $price['invl_start'] < 0) {
                throw new \Exception(ErrorCode::errorParam('prices interval start'));
            }
            if (!is_numeric($price['invl_end']) || $price['invl_end'] < $price['invl_start']) {
                throw new \Exception(ErrorCode::errorParam('price interval end'));
            }
            if (!is_numeric($price['price']) || $price['price'] < 0) {
                throw new \Exception(ErrorCode::errorParam('price'));
            }
            if (isset($start[$price['invl_start']])) {
                throw new \Exception(ErrorCode::errorParam('prices start repeat'));
            }
            if (isset($end[$price['invl_end']])) {
//                echo json_encode($prices);
                throw new \Exception(ErrorCode::errorParam('prices end repeat'));
            }
            $start[$price['invl_start']] = $i;
            $end[$price['invl_end']] = $i;
        }
        $startKey = array_keys($start);
        sort($startKey);
        $endKey = array_keys($end);
        sort($endKey);
        $tempPrices = [];
        for ($i = 0; $i < count($endKey); $i++) {
            if ($i < count($endKey) - 1) {
                if ($startKey[$i + 1] != $endKey[$i]) {
                    throw new \Exception(ErrorCode::errorParam('prices sort'));
                }
            }
            array_push($tempPrices, $prices[$end[$endKey[$i]]]);
        }
        return $tempPrices;
    }

    public static function createStatDayForCompany($companyId, $startTimestamp, $endTimestamp, $companyTimezone)

    {
        $timezone = new \DateTimeZone($companyTimezone);
        $createTime = new \DateTime("@{$startTimestamp}");
        $createTime->setTimezone($timezone);
        $nowTime = new \DateTime("@{$endTimestamp}");
        $nowTime->setTimezone($timezone);
        $interval = $nowTime->diff($createTime);
        for ($i = 0; $i <= $interval->days + 7; $i++) {
            $settleDayString = $createTime->format('Y-m-d');
            $dayTimestamp = strtotime($settleDayString);
            $offset = $createTime->format('Z');
            $gmtDayTimestamp = $dayTimestamp - $offset;
            $gmtDayTime = MethodAlgorithm::formatTimestampToDate($gmtDayTimestamp);

            $stat_month = $createTime->format('n');
            $stat_year = $createTime->format('Y');
            $stat_week = $createTime->format('W');
            $stat_week_year = ($stat_month == 1 && $stat_week > 50) ? ($stat_year - 1) : $stat_year;
            BookingDayStatistic::firstOrCreate(
                [
                    "company_id" => $companyId,
                    "stat_date" => $gmtDayTime,
                    "stat_day" => $createTime->format('z'),
                    "stat_month" => $createTime->format('n'),
                    "stat_year" => $createTime->format('Y'),
                    "stat_week" => $createTime->format('W'),
                    "stat_week_year" => $stat_week_year,
                ]
            );
            date_add($createTime, date_interval_create_from_date_string("1 day"));
        }
    }

    public static function shiftString($arr, $right = false)
    {
        // 将数组重新合并为一整个字符串
        $str1 = implode($arr);
        if ($right) {
            // 取后两位
            $end = substr($str1, -2);
            // 取后两位以前
            $start = substr($str1, 0, -2);
        } else {
            // 取前两位
            $start = substr($str1, 0, 2);
            // 取两位以后
            $end = substr($str1, 2);
        }

        $tempStr = $end . $start;
        // 分割字符串
        $splitArr = str_split($tempStr, 48);
        // 重新合并
        $final = implode('","', $splitArr);
        $finalStr = '["' . $final . '"]';
        return $finalStr;
    }

    public static function checkDstForOffer($offer_id, $appointed_time = null)
    {
        $company = Company::leftjoin('offers', 'offers.company_id', '=', 'companies.id')
            ->where('offers.id', $offer_id)
            ->select('companies.timezone')
            ->first();
        if (empty($appointed_time)) {
            $date = new \DateTime();
        } else {
            $date = new \DateTime("@{$appointed_time}");
        }
        $date->setTimezone(new \DateTimeZone($company->timezone));
        return $date->format("I");
    }

    public static function checkDstForDriver($driver_id, $appointed_time = null)
    {
        $company = Company::leftjoin('users', 'users.company_id', '=', 'companies.id')
            ->leftjoin('drivers', 'drivers.user_id', '=', 'users.id')
            ->where('drivers.id', $driver_id)
            ->select('companies.timezone')
            ->first();
        if (empty($appointed_time)) {
            $date = new \DateTime();
        } else {
            $date = new \DateTime("@{$appointed_time}");
        }
        $date->setTimezone(new \DateTimeZone($company->timezone));
        return $date->format("I");
    }

    public static function checkDstForCar($car_id, $appointed_time = null)
    {
        $company = Company::leftjoin('cars', 'cars.company_id', '=', 'companies.id')
            ->where('cars.id', $car_id)
            ->select('companies.timezone')
            ->first();
        if (empty($appointed_time)) {
            $date = new \DateTime();
        } else {
            $date = new \DateTime("@{$appointed_time}");
        }
        $date->setTimezone(new \DateTimeZone($company->timezone));
        return $date->format("I");
    }


    public static function checkDstForCompany($company_id, $appointed_time = null)
    {
        $company = Company::where('id', $company_id)
            ->select('timezone')
            ->first();
        if (empty($appointed_time)) {
            $date = new \DateTime();
        } else {
            $date = new \DateTime("@{$appointed_time}");
        }
        $date->setTimezone(new \DateTimeZone($company->timezone));
        return $date->format("I");
    }


    public static function emailRegex($email)
    {
        return preg_match("/([\w\-]+\@[\w\-]+\.[\w\-]+)/", $email);
    }
    public static function pwdRegex($pwd)
    {
        return preg_match("/\w{6,16}$/", $pwd);
    }

    public static function mobileRegex($mobile)
    {
        return !is_null($mobile);
    }


    public static function urlMatchRegex($url)
    {
        $regex = '@(?i)\b((?:[a-z][\w-]+:(?:/{1,3}|[a-z0-9%])|www\d{0,3}[.]|[a-z0-9.\-]+[.][a-z]{2,4}/)(?:[^\s()<>]+|\(([^\s()<>]+|(\([^\s()<>]+\)))*\))+(?:\(([^\s()<>]+|(\([^\s()<>]+\)))*\)|[^\s`!()\[\]{};:\'".,<>?«»“”‘’]))@';
        return preg_match($regex, $url);
    }


    public static function zipCodeMatchRegex($code)
    {
//        $regex = '/^[0-9]{5}$/';
//        return preg_match($regex, $code);
        return true;
    }

    /**
     * @param $type
     * @param $cost
     * @return string
     */
    public static function ccyCvt($type, $cost)
    {
        $money = new Money($cost, $type);
        return $money->format();
    }

    /**
     * @param $distance
     * @param $bookUnit
     * @param $comUnit
     * @return string
     */
    public static function getUnitType($distance, $bookUnit, $comUnit)
    {
        if($comUnit == $bookUnit){
            if (($bookUnit) == CompanySetting::UNIT_MI) {
                return round($distance,2)."MI";
            } else {
                return round($distance,2)."KM";
            }
        }else{
            if ($bookUnit == CompanySetting::UNIT_MI) {
                return round($distance*Constants::MI_2_KM,2)."KM";
            } else {
                return round($distance*Constants::KM_2_MI,2)."MI";
            }
        }
    }
}