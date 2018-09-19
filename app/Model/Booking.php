<?php

namespace App\Model;

use App\Constants;
use App\Method\GeoLocationAlgorithm;
use App\Method\KARLDateTime;
use App\Method\MethodAlgorithm;
use App\Method\UrlSpell;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;


/**
 * Class Booking
 *
 * @property string $company_id
 * @property string $exe_com_id
 * @property string $car_id
 * @property string $driver_id
 * @property string $customer_id
 * @property string $passenger_names
 * @property string $passenger_count
 * @property string $bags_count
 * @property string $type
 * @property string $appointed_at
 * @property string $d_lat
 * @property string $d_lng
 * @property string $estimate_time
 * @property string $estimate_distance
 * @property string $a_lat
 * @property string $a_lng
 * @property string $offer_id
 * @property string $user_id
 * @property string $base_cost
 * @property string $option_cost
 * @property string $total_cost
 * @property string $free_fee
 * @property string $message
 * @property string $coupon
 * @property string $coupon_off
 * @property string $card_token
 * @property string $option_data
 * @property string $driver_data
 * @property string $customer_data
 * @property string $car_data
 * @property string $offer_data
 * @property string $card_data
 * @property string $a_address
 * @property string $d_address
 * @property string $d_is_airport
 * @property string $a_is_airport
 * @property string $reject
 * @property string $custom_auth_code
 * @property string $tva
 * @package App\Model
 */

class Booking extends Model
{

    const CHECK_TYPE_DISTANCE = Offer::CHECK_TYPE_DISTANCE;
    const CHECK_TYPE_HOURLY = Offer::CHECK_TYPE_HOURLY;
    const CHECK_TYPE_CUSTOM = Offer::CHECK_TYPE_CUSTOM;

    const REJECT_TYPE_NORMAL = 0;
    const REJECT_TYPE_REJECT = 1;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'company_id',
        'exe_com_id',
        'car_id',
        'driver_id',
        'customer_id',
        'passenger_names',
        'passenger_count',
        'bags_count',
        'type',
        'appointed_at',
        'appointed_at_pickup',
        'd_lat',
        'd_lng',
        'estimate_time',
        'unit',
        'estimate_distance',
        'a_lat',
        'a_lng',
        'offer_id',
        'user_id',
        'base_cost',                   //offer æŒ‰æ—¶é•¿æˆ–è·ç¦»ç®—å‡ºçš„åŸºç¡€èŠ±è´¹
        'option_cost',                 // option æ‰€éœ€è´¹ç”¨
        'total_cost',                  // base_cost + option_cost
        'free_fee',
        'message',
        'coupon',
        'coupon_off',
    //'pay_card_data',
        'card_token',
        'option_data',
        'driver_data',
        'customer_data',
        'car_data',
        'offer_data',
        'card_data',
        'a_address',
        'd_address',
        'd_is_airport',
        'a_is_airport',
        'reject',
        'custom_auth_code',
        'tva'
    ];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = [
        'created_at', 'updated_at'
    ];


    public static function getBookingDetail($booking_id, $company_id, $driver_id = null)
    {
        if (is_null($driver_id)) {
            $selector = [
                'bookings.id',
                'bookings.company_id',
                'bookings.exe_com_id',
                'bookings.reject',
                'own_com.name as own_com_name',
                'exe_com.name as exe_com_name',
                'own_com.email as own_com_email',
                'exe_com.email as exe_com_email',
                'own_com.phone1 as own_com_phone1',
                'exe_com.phone1 as exe_com_phone1',
                'own_com.phone2 as own_com_phone2',
                'exe_com.phone2 as exe_com_phone2',
                DB::raw('UNIX_TIMESTAMP(bookings.appointed_at) AS appointed_at'),
                DB::raw('UNIX_TIMESTAMP(bookings.appointed_at) as temp_appointed_at'),
                DB::raw("case when orders.trip_state>" . Order::TRIP_STATE_WAITING_TO_SETTLE . "
                 then
                  orders.actual_fee
                 else
                  bookings.total_cost
                  end as cost"),
                'bookings.base_cost',
                'bookings.d_address',
                'bookings.d_lng',
                'bookings.d_lat',
                'bookings.a_address',
                'bookings.a_lng',
                'bookings.a_lat',
                'bookings.type',
                'bookings.unit',
                DB::raw("case when orders.trip_state>=" . Order::TRIP_STATE_WAITING_DRIVER_DETERMINE . "
                    then orders.actual_time 
                    else bookings.estimate_time
                    end as detail_time
                "),
                DB::raw("case when orders.trip_state>=" . Order::TRIP_STATE_WAITING_DRIVER_DETERMINE . "
                    then orders.actual_distance
                    else bookings.estimate_distance
                    end as detail_distance
                "),
                'bookings.estimate_time',
                'bookings.estimate_distance',
                'bookings.customer_id',
                'bookings.car_data',
                'bookings.driver_data',
                'bookings.customer_data',
                'bookings.offer_data',
                'bookings.option_data',
                'bookings.coupon',
                'bookings.coupon_off',
                'bookings.message',
                'bookings.passenger_count',
                'bookings.bags_count',
                'bookings.passenger_names',
                'bth.ccy',
                'booking_airlines.a_airline',
                'booking_airlines.a_airline_code',
                'booking_airlines.d_airline',
                'booking_airlines.d_airline_code',
                'booking_airlines.a_flight',
                'booking_airlines.d_flight',
                DB::raw('unix_timestamp(bookings.created_at) AS created_at'),
                'orders.trip_state',
                'orders.order_state',
                DB::raw('unix_timestamp(ifnull(orders.departure_time, \'0000-00-00\')) AS departure_time'),
                DB::raw('unix_timestamp(ifnull(orders.reach_time, \'0000-00-00\')) AS reach_time'),
                DB::raw('unix_timestamp(ifnull(orders.start_time, \'0000-00-00\')) AS start_time'),
                DB::raw('unix_timestamp(ifnull(orders.finish_time, \'0000-00-00\')) AS finish_time'),
                DB::raw('unix_timestamp(ifnull(orders.settle_time, \'0000-00-00\')) AS settle_time'),
                'orders.actual_distance',
                'orders.actual_time',
                'orders.feedbacked',
                'orders.invoice_sent',
                'feedbacks.appearance',
                'feedbacks.cleanliness',
                'feedbacks.comment',
                'feedbacks.driving_ability',
                'feedbacks.quality',
                'feedbacks.professionalism'
            ];
        } else {
            $selector = [
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
                'bookings.estimate_time',
                'bookings.estimate_distance',
                'bookings.message',
                'bookings.coupon',
                'bookings.coupon_off',
                'bookings.passenger_count',
                'bookings.bags_count',
                'bookings.unit',
                'bth.ccy',
                'bookings.passenger_names',
                'booking_airlines.a_airline',
                'booking_airlines.d_airline',
                'booking_airlines.a_flight',
                'booking_airlines.d_flight',
                'oc.name as own_company_name',
                'own_com.id as own_company_id',
                DB::raw(UrlSpell::getUrlSpell()->getCompaniesLogoByNameInDB('own_company_logo', 'oc')),
                'exe_com.name as exe_company_name',
                'exe_com.id as exe_company_id',
                'company_settings.hide_driver_fee',
                DB::raw(UrlSpell::getUrlSpell()->getCompaniesLogoByNameInDB('exe_company_logo', 'ec'))
            ];
        }


        $booking =  Booking::leftjoin("booking_airlines", "bookings.id", "=", "booking_airlines.booking_id")
            ->leftjoin("booking_transaction_histories as bth","bth.booking_id","=","bookings.id")
            ->leftjoin('orders', 'orders.booking_id', '=', 'bookings.id')
            ->leftjoin('feedbacks', 'orders.id', '=', 'feedbacks.order_id')
            ->leftjoin("companies as own_com", "bookings.company_id", "=", "own_com.id")
            ->leftjoin("companies as exe_com", "bookings.exe_com_id", "=", "exe_com.id")
            ->leftjoin("company_settings", "exe_com.id", "=", "company_settings.company_id")
            ->where(function ($query) use ($driver_id){
                if(!is_null($driver_id)){
                    $query->where('bookings.driver_id', $driver_id)
                        ->where('bookings.reject', Booking::REJECT_TYPE_NORMAL);
                }
            })
            ->where('bookings.id', $booking_id)
            ->where(function ($query) use ($company_id , $driver_id) {
                if(is_null($driver_id)){

                $query->where("bookings.company_id", $company_id)
                    ->orWhere("bookings.exe_com_id", $company_id);
                }else{
                    $query->where("bookings.exe_com_id", $company_id);
                }
            })
            ->where('bookings.id', $booking_id)
            ->select(
                $selector
            )
            ->first();

            if(count($booking) == 1)
            {
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
                
                $booking->temp_appointed_at = $date->format("h:ia");
            }

            return $booking;
    }

    public static function getBookingForInvoice($booking_id)
    {
        $trip = Booking::leftjoin('companies', 'companies.id', '=', 'bookings.company_id')
            ->leftjoin("company_settings","company_settings.company_id","=","companies.id")
            ->leftjoin("booking_transaction_histories as bth","bth.booking_id","=","bookings.id")
            ->leftjoin('orders', 'orders.booking_id', '=', 'bookings.id')
            ->leftjoin('bills', 'bills.booking_id', '=', 'bookings.id')
            ->leftjoin('booking_airlines as airlines', 'airlines.booking_id', '=', 'bookings.id')
            ->where('bookings.id', $booking_id)
            ->select(
                DB::raw(UrlSpell::getUrlSpell()->getCompaniesLogoByNameInDB('company_logo')),
                'bookings.company_id',
                'bills.settle_fee',
                'bills.an_fee',
                'bills.com_income',
                'bills.platform_income',
                'bookings.type',
                'bookings.tva',
                'bookings.option_cost',
                'bookings.base_cost',
                'bookings.driver_data',
                'bookings.customer_data',
                'bookings.offer_data',
                'bookings.d_address',
                'bookings.a_address',
                'bookings.d_lat',
                'bookings.d_lng',
                'bookings.a_lat',
                'bookings.a_lng',
                'bookings.estimate_time',
                'bookings.coupon',
                'bookings.coupon_off',
                'bth.ccy',
                'bookings.unit',
                'bookings.estimate_distance',
                'orders.actual_time as duration',
                'orders.actual_distance as distance',
                'companies.name as company_name',
                'companies.phone1 as company_phone1',
                'companies.phone2 as company_phone2',
                'companies.email as company_email',
                'companies.email_host as company_email_host',
                'companies.email_port as company_email_port',
                'companies.email_password as company_email_pwd',
                'company_settings.lang as lang',
                'company_settings.distance_unit as com_unit',
                'airlines.d_airline',
                'airlines.d_flight',
                'airlines.a_airline',
                'airlines.a_flight',
                DB::raw('unix_timestamp(orders.start_time) as start_time'),
                DB::raw('unix_timestamp(orders.finish_time) as finish_time')
            )
            ->first();
        $trip->company_number = $trip->company_phone1==null?$trip->company_phone2:$trip->company_phone1;

        $d_address = json_decode($trip->d_address)==null? $trip->d_address:json_decode($trip->d_address)->formatted_address;
        $trip->d_address = $d_address;
        $a_address = json_decode($trip->a_address)==null? $trip->a_address:json_decode($trip->a_address)->formatted_address;
        $trip->a_address = $a_address;

        $driver = json_decode($trip->driver_data);
        $trip->driver_name = $driver->first_name." ".$driver->last_name;
        $trip->driver_avatar = $driver->avatar_url;

        $customer = json_decode($trip->customer_data);
        $trip->customer_name = $customer->first_name." ".$customer->last_name;
        $trip->customer_avatar = $customer->avatar_url;

        $timezone = GeoLocationAlgorithm::getInstance()
            ->getLocationTime($trip->d_lat,$trip->d_lng, $trip->start_time);
        $timezoneCode = (!is_null($timezone) && isset($timezone->timeZoneId))?$timezone->timeZoneId:"UTC";
        $time = new KARLDateTime($trip->start_time);
        $time->setTimezone(new \DateTimeZone($timezoneCode));
        $time->setLanguage($trip->lang);
        $trip->startTime = $time;

        if ($trip->type == Booking::CHECK_TYPE_DISTANCE) {
            $timezone = GeoLocationAlgorithm::getInstance()
                ->getLocationTime($trip->a_lat,$trip->a_lng, $trip->finish_time);
            $timezoneCode = (!is_null($timezone) && isset($timezone->timeZoneId))?$timezone->timeZoneId:"UTC";
            $time = new KARLDateTime($trip->finish_time);
            $time->setTimezone(new \DateTimeZone($timezoneCode));
            $time->setLanguage($trip->lang);
            $trip->finishTime = $time;
        }

        $sub_total = $trip->settle_fee / (1 + $trip->tva / 100);
        $trip->tax = round($sub_total * ($trip->tva / 100), 2);
        $trip->add_ons = round(($trip->option_cost) / (1 + $trip->tva / 100), 2);
        $trip->base_fare = round(($trip->base_cost) / (1 + $trip->tva / 100), 2);
        $trip->sub_total = $trip->add_ons + $trip->base_fare;
        $trip->additional = round(($trip->settle_fee - $trip->base_cost - $trip->option_cost)/(1+$trip->tva/100),2)+$trip->coupon_off;
        $trip->additional = $trip->additional > 1 ? $trip->additional : 0;

        $offer_data = json_decode($trip->offer_data);
        $rate = 0;
        if(isset($offer_data->prices)){
            $prices = json_decode($offer_data->prices, true);

            if ($trip->type == \App\Model\Offer::CHECK_TYPE_DISTANCE) {
                if($trip->com_unit == $trip->unit){
                    $match = $trip->distance;
                }else{
                    if($trip->com_unit == Constants::UNIT_KM){
                        $match = $trip->distance * Constants::MI_2_KM;
                    }else{
                        $match = $trip->distance * Constants::KM_2_MI;
                    }
                }

            } else {
                $match = $trip->duration;
            }

            if ($match < $prices[0]['invl_start']) {
                $rate = $prices[0]['price'];
            }

            if ($match > $prices[count($prices) - 1]['invl_end']) {
                $rate = $prices[count($prices) - 1]['price'];
            }

            for ($i = 0; $i < count($prices); $i++) {
                $price = $prices[$i];
                if ($match >= $price['invl_start'] && $match <= $price['invl_end']) {
                    $rate = $price['price'];
                }
            }
        }else{
            $rate = $offer_data->price;
        }

        $trip->rate = $rate;
        $trip->calc_method = $offer_data->calc_method;

        $trip->total_time = $trip->duration;
        if ($trip->total_time % 60 == 0) {
            $trip->total_time = floor($trip->total_time / 60) . ":00";
        }else if ($trip->total_time % 60 > 0 && $trip->total_time % 60 < 10) {
            $trip->total_time = floor($trip->total_time / 60) . ":0" . round($trip->total_time % 60);
        }else {
            $trip->total_time = floor($trip->total_time / 60) . ":" . round($trip->total_time % 60);
        }

        $trip->distance = round($trip->distance, 2);
        $trip->estimate_time = MethodAlgorithm::formatTime($trip->estimate_time);
        $trip->android=$_SERVER['local_url']."/app/company/{$trip->company_id}/android";
        $trip->ios=$_SERVER['local_url']."/app/company/{$trip->company_id}/ios";
        return $trip;
    }

}
