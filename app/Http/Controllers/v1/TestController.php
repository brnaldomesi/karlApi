<?php

namespace App\Http\Controllers\v1;

use App\Constants;
use App\ErrorCode;
use App\Jobs\CustomerCheckGroupJob;
use App\Jobs\SendEmailAdminPasswordJob;
use App\Jobs\SendEmailResetSalePasswordJob;
use App\Method\GeoLocationAlgorithm;
use App\Method\KARLDateTime;
use App\Method\MethodAlgorithm;
use App\Method\UrlSpell;
use App\Model\Bill;
use App\Model\Booking;
use App\Model\Company;
use App\Model\Customer;
use App\Model\Driver;
use App\Model\TransRecord;
use Curl\Curl;
use DrewM\MailChimp\MailChimp;
use Faker\Provider\Uuid;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Lang;
use Stripe\BalanceTransaction;
use Stripe\Charge;
use Stripe\Coupon;
use Stripe\Stripe;
use Stripe\Transfer;

class TestController extends Controller
{
    public function bookingEmail($bookingId, $lang)
    {
        $booking = Booking::leftjoin("companies", "bookings.company_id", "=", "companies.id")
            ->leftjoin("company_settings","company_settings.company_id","=","companies.id")
            ->leftjoin("booking_transaction_histories as bth", "bth.booking_id", "=", "bookings.id")
            ->where('bookings.id', $bookingId)
            ->select(
                DB::raw(UrlSpell::getUrlSpell()->getCompaniesLogoByNameInDB("own_com_logo")),
                "bookings.company_id",
                "bookings.total_cost",
                "bookings.d_lat",
                "bookings.d_lng",
                "bookings.d_address",
                "bookings.a_address",
                "bookings.estimate_time",
                "bookings.estimate_distance",
                "bookings.car_data",
                "bookings.driver_data",
                "bookings.customer_data",
                "bookings.passenger_count",
                "bookings.bags_count",
                "bookings.message",
                "bookings.type",
                "bth.ccy",
                "bookings.unit",
                "company_settings.distance_unit as com_unit",
                DB::raw("unix_timestamp(bookings.appointed_at) as appointed_at")
            )
            ->first();
        $booking->customer_data = json_decode($booking->customer_data);
        $booking->car_data = json_decode($booking->car_data);
        $booking->driver_data = json_decode($booking->driver_data);
        $d_address = json_decode($booking->d_address) == null ? $booking->d_address : json_decode($booking->d_address)->formatted_address;
        $a_address = json_decode($booking->a_address) == null ? $booking->a_address : json_decode($booking->a_address)->formatted_address;
        $booking->d_address = $d_address;
        $booking->a_address = $a_address;
        $booking->estimate_time = MethodAlgorithm::formatTime($booking->estimate_time);

//        $timeInfo = GeoLocationAlgorithm::getInstance()
//            ->getLocationTime($booking->d_lat,$booking->d_lng,$booking->appointed_at);
        $booking->timezone = "GMT+8";//$timeInfo->timeZoneId;
        $date = new KARLDateTime($booking->appointed_at);
        $date->setTimezone(new \DateTimeZone($booking->timezone));
        $date->setLanguage($lang);
        $booking->appointed_at = $date;
        app('translator')->setLocale($lang);
        return view("create_booking_email", ["booking" => $booking, "lang" => $lang])->render();
    }


    public function sendPassword($password)
    {
        return view('reset_admin_password', ["password" => $password])->render();
    }

    public function sendBookingInvoice($bookingId, $lang)
    {
        $trip = Booking::leftjoin('companies', 'companies.id', '=', 'bookings.company_id')
            ->leftjoin("company_settings","company_settings.company_id","=","companies.id")
            ->leftjoin('orders', 'orders.booking_id', '=', 'bookings.id')
            ->leftjoin("booking_transaction_histories as bth", "bth.booking_id", "=", "bookings.id")
            ->leftjoin('bills', 'bills.booking_id', '=', 'bookings.id')
            ->leftjoin('booking_airlines as airlines', 'airlines.booking_id', '=', 'bookings.id')
            ->where('bookings.id', $bookingId)
            ->select(
                DB::raw(UrlSpell::getUrlSpell()->getCompaniesLogoByNameInDB('company_logo')),
                'bookings.company_id',
                'bills.settle_fee',
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
                'bookings.unit',
                'bth.ccy',
                'bookings.estimate_time',
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
                'company_settings.distance_unit as com_unit',
                'airlines.d_airline',
                'airlines.d_flight',
                'airlines.a_airline',
                'airlines.a_flight',
                DB::raw('unix_timestamp(orders.start_time) as start_time'),
                DB::raw('unix_timestamp(orders.finish_time) as finish_time')
            )
            ->first();

        $trip->company_number = $trip->company_phone1 == null ? $trip->company_phone2 : $trip->company_phone1;

        $d_address = json_decode($trip->d_address) == null ? $trip->d_address : json_decode($trip->d_address)->formatted_address;
        $trip->d_address = $d_address;
        $a_address = json_decode($trip->a_address) == null ? $trip->a_address : json_decode($trip->a_address)->formatted_address;
        $trip->a_address = $a_address;

        $driver = json_decode($trip->driver_data);
        $trip->driver_name = $driver->first_name . " " . $driver->last_name;
        $trip->driver_avatar = $driver->avatar_url;

        $customer = json_decode($trip->customer_data);
        $trip->customer_name = $customer->first_name . " " . $customer->last_name;
        $trip->customer_avatar = $customer->avatar_url;

//        $timezone = GeoLocationAlgorithm::getInstance()
//            ->getLocationTime($trip->d_lat,$trip->d_lng, $trip->start_time);
        $trip->timezone = "Asia/Shanghai";//$timeInfo->timeZoneId;

        $time = new KARLDateTime($trip->start_time);
        $time->setTimezone(new \DateTimeZone($trip->timezone));
        $time->setLanguage($lang);
        $trip->startTime = $time;

        if ($trip->type == Booking::CHECK_TYPE_DISTANCE) {
//            $timezone = GeoLocationAlgorithm::getInstance()
//                ->getLocationTime($trip->a_lat,$trip->a_lng, $trip->finish_time);
            $time = new KARLDateTime($trip->finish_time);
            $time->setTimezone(new \DateTimeZone($trip->timezone));
            $time->setLanguage($lang);
            $trip->finishTime = $time;
        }

        $sub_total = $trip->settle_fee / (1 + $trip->tva / 100);
        $trip->tax = round($sub_total * ($trip->tva / 100), 2);
        $trip->sub_total = $trip->settle_fee - $trip->tax;
        $trip->add_ons = round(($trip->option_cost) / (1 + $trip->tva / 100), 2);
        $trip->base_fare = round(($trip->base_cost) / (1 + $trip->tva / 100), 2);
        $trip->additional = $trip->sub_total - $trip->base_fare - $trip->add_ons;
        $trip->additional = $trip->additional > 1 ? $trip->additional : 0;

        $offer_data = json_decode($trip->offer_data);
        $rate = 0;
        if (isset($offer_data->prices)) {
            $prices = json_decode($offer_data->prices, true);

            if ($trip->type == \App\Model\Offer::CHECK_TYPE_DISTANCE) {
                $match = $trip->distance;
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
        } else {
            $rate = $offer_data->price;
        }

        $trip->rate = $rate;
        $trip->calc_method = $offer_data->calc_method;

        $trip->total_time = $trip->duration;
        if ($trip->total_time % 60 == 0) {
            $trip->total_time = floor($trip->total_time / 60) . ":00";
        } else if ($trip->total_time % 60 > 0 && $trip->total_time % 60 < 10) {
            $trip->total_time = floor($trip->total_time / 60) . ":0" . round($trip->total_time % 60);
        } else {
            $trip->total_time = floor($trip->total_time / 60) . ":" . round($trip->total_time % 60);
        }

        $trip->distance = round($trip->distance, 2);
        $trip->show_type = 0;
        if (strtolower($lang) == 'fr') {
            $trip->android_app = $_SERVER['local_url'] . "/imgs/common/google_fr_app";
            $trip->ios_app = $_SERVER['local_url'] . "/imgs/common/ios_fr_app";
        } else {
            $trip->android_app = $_SERVER['local_url'] . "/imgs/common/google_app";
            $trip->ios_app = $_SERVER['local_url'] . "/imgs/common/ios_app";
        }
        var_dump($trip);
        \app('translator')->setLocale($lang);
        return view('customer_invoice_email', ['trip' => $trip, "lang" => $lang])->render();
    }

    public function bookingDetailEmail($bookingId)
    {
        $info = Booking::leftjoin("companies", "bookings.company_id", "=", "companies.id")
            ->where('bookings.id', $bookingId)
            ->select(
                DB::raw(UrlSpell::getUrlSpell()->getCompaniesLogoByNameInDB("company_logo")),
                "bookings.company_id",
                "bookings.total_cost",
                "bookings.d_lat",
                "bookings.d_lng",
                "bookings.d_address",
                "bookings.a_address",
                "bookings.estimate_time",
                "bookings.estimate_distance",
                "bookings.car_data",
                "bookings.driver_data",
                "bookings.customer_data",
                "bookings.passenger_count",
                "bookings.bags_count",
                "bookings.type",
                "companies.name as company_name",
                "companies.phone1 as company_number",
                "companies.email as company_email",
                "companies.email_host as email_host",
                "companies.email_port as email_port",
                "companies.email_password as email_password",
                DB::raw("unix_timestamp(bookings.appointed_at) as appointed_at")
            )
            ->first();
        $info->customer_data = json_decode($info->customer_data);
        $info->car_data = json_decode($info->car_data);
        $info->driver_data = json_decode($info->driver_data);
        $d_address = json_decode($info->d_address) == null ? $info->d_address : json_decode($info->d_address)->formatted_address;
        $a_address = json_decode($info->a_address) == null ? $info->a_address : json_decode($info->a_address)->formatted_address;
        $info->d_address = $d_address;
        $info->a_address = $a_address;
        $info->estimate_time = MethodAlgorithm::formatTime($info->estimate_time);

        $timeInfo = GeoLocationAlgorithm::getInstance()
            ->getLocationTime($info->d_lat, $info->d_lng, $info->appointed_at);
        $info->timezone = isset($timeInfo->timeZoneId) ? $timeInfo->timeZoneId : "Asia/Shanghai";
        $date = new \DateTime("@{$info->appointed_at}");
        $date->setTimezone(new \DateTimeZone($info->timezone));
        $info->appointed_at = $date;

        return view("booking_detail", ["info" => $info])->render();
    }


    public function bookingAnCompanyA($bookingId, $lang)
    {
        $booking = $this->getBooking($bookingId);
        $booking->appointed_at->setLanguage($lang);
        app('translator')->setLocale($lang);
        $booking->an_type = 1;
        return view("create_booking_email", ["booking" => $booking, "lang" => $lang])->render();
    }

    public function bookingAnCompanyB($bookingId, $lang)
    {
        $booking = $this->getBooking($bookingId);
        $booking->appointed_at->setLanguage($lang);

        $booking->an_type = 2;
        $booking->com_unit = $booking->exe_unit;
//        echo json_encode($booking);
        app('translator')->setLocale($lang);
        return view("create_booking_email", ["booking" => $booking, "lang" => $lang])->render();
    }

    private function getBooking($bookingId)
    {
        $booking = Booking::leftjoin("companies as own_com", "bookings.company_id", "=", "own_com.id")
            ->leftjoin("companies as exe_com", "bookings.exe_com_id", "=", "exe_com.id")
            ->leftjoin(DB::raw("(select users.email,users.company_id from admins 
                                    left join users on 
                                    admins.user_id=users.id) as own_admin"),
                "bookings.company_id", "=", "own_admin.company_id")
            ->leftjoin(DB::raw("(select users.email,users.company_id from admins 
                                    left join users on 
                                    admins.user_id=users.id) as exe_admin"),
                "bookings.exe_com_id", "=", "exe_admin.company_id")
            ->leftjoin("booking_transaction_histories as bth", "bth.booking_id", "=", "bookings.id")
            ->leftjoin("company_settings as own_com_set","own_com_set.company_id","=","own_com.id")
            ->leftjoin("company_settings as exe_com_set","exe_com_set.company_id","=","exe_com.id")
            ->where("bookings.id", $bookingId)
            ->select(
                DB::raw(UrlSpell::getUrlSpell()->getCompaniesLogoByNameInDB("own_company_logo", "own_com")),
                DB::raw(UrlSpell::getUrlSpell()->getCompaniesLogoByNameInDB("exe_company_logo", "exe_com")),
                "own_com.name as own_com_name",
                "exe_com.name as exe_com_name",
                "own_com.email as own_com_email",
                "exe_com.email as exe_com_email",
                "own_com.phone1 as own_com_phone1",
                "exe_com.phone1 as exe_com_phone1",
                "own_com.phone2 as own_com_phone2",
                "exe_com.phone2 as exe_com_phone2",
                "own_admin.email as own_admin_email",
                "exe_admin.email as exe_admin_email",
                "bookings.company_id",
                "bookings.exe_com_id",
                "bookings.total_cost",
                "bookings.d_lat",
                "bookings.d_lng",
                "bookings.d_address",
                "bookings.a_address",
                "bookings.estimate_time",
                "bookings.estimate_distance",
                "bookings.car_data",
                "bookings.driver_data",
                "bookings.customer_data",
                "bookings.passenger_count",
                "bookings.bags_count",
                "bookings.message",
                "bookings.type",
                "bth.ccy",
                "own_com_set.distance_unit as com_unit",
                "exe_com_set.distance_unit as exe_unit",
                DB::raw("unix_timestamp(bookings.appointed_at) as appointed_at"),
                DB::raw("unix_timestamp(bookings.created_at) as created")
            )
            ->first();

        $booking->customer_data = json_decode($booking->customer_data);
        $booking->car_data = json_decode($booking->car_data);
        $booking->driver_data = json_decode($booking->driver_data);
        $d_address = json_decode($booking->d_address) == null ? $booking->d_address : json_decode($booking->d_address)->formatted_address;
        $a_address = json_decode($booking->a_address) == null ? $booking->a_address : json_decode($booking->a_address)->formatted_address;
        $booking->d_address = $d_address;
        $booking->a_address = $a_address;
        $booking->estimate_time = MethodAlgorithm::formatTime($booking->estimate_time);

//        $timeInfo = GeoLocationAlgorithm::getInstance()
//            ->getLocationTime($booking->d_lat,$booking->d_lng,$booking->appointed_at);
        $booking->timezone = "Asia/Shanghai";//$timeInfo->timeZoneId;
        $date = new KARLDateTime($booking->appointed_at);
        $date->setTimezone(new \DateTimeZone($booking->timezone));
        $booking->appointed_at = $date;
        $created_at = new KARLDateTime($booking->created);
        $booking->created = $created_at;

        return $booking;
    }

    public function readTempFile()
    {
        $fileName = Input::get("file", "lumen.log");
        $file_path = storage_path() . "/logs/" . $fileName;
        return response(File::get($file_path))->header('Content-Type', 'text/plain');
    }

    public function test($token)
    {
//        $uuid = Uuid::uuid();
//        return str_replace('-','',$uuid);
        return $token;
    }


    public function sendAdminPwd()
    {
        $this->dispatch(new SendEmailAdminPasswordJob("liqihai1987@gmail.com", "123456"));
    }


    public function testStripeConnect($lang)
    {
//        \app("translator",["lang"=>$lang]);
//        return Lang::get("language.yes",["lang"=>$lang]);
        app('translator')->setLocale($lang);

        return view("test_view");
    }


    public function resetAdminPwd($lang)
    {
        \app('translator')->setLocale($lang);
        return view('re_set_ad_pwd_email', ["password" => "123456", "type" => 2])->render();
    }

    public function newAdminPwd($lang)
    {
        \app('translator')->setLocale($lang);
        return view('re_set_ad_pwd_email', ["password" => "123456", "type" => 1])->render();
    }

    public function resetDriverPwd($lang)
    {
        $info = Company::where("id", 1)
            ->first();
        $info->email = "ccav@ctc.cc";
        $info->password = "123456";
        if (strtolower($lang) == 'fr') {
            $info->android_app = $_SERVER['local_url'] . "/imgs/common/google_fr_app";
            $info->ios_app = $_SERVER['local_url'] . "/imgs/common/ios_fr_app";
        } else {
            $info->android_app = $_SERVER['local_url'] . "/imgs/common/google_app";
            $info->ios_app = $_SERVER['local_url'] . "/imgs/common/ios_app";
        }
        \app('translator')->setLocale($lang);
        return view('re_set_dr_pwd_email', ["info" => $info, "type" => 2])->render();
    }

    public function newDriverPwd($lang)
    {
        $info = Company::where("id", 1)
            ->first();
        $info->email = "ccav@ctc.cc";
        $info->password = "123456";
        if (strtolower($lang) == 'fr') {
            $info->android_app = $_SERVER['local_url'] . "/imgs/common/google_fr_app";
            $info->ios_app = $_SERVER['local_url'] . "/imgs/common/ios_fr_app";
        } else {
            $info->android_app = $_SERVER['local_url'] . "/imgs/common/google_app";
            $info->ios_app = $_SERVER['local_url'] . "/imgs/common/ios_app";
        }
        \app('translator')->setLocale($lang);
        return view('re_set_dr_pwd_email', ["info" => $info, "type" => 1])->render();
    }

    public function resetClientPwd($lang)
    {
        $info = Company::where("id", 1)
            ->first();
        $info->email = "ccav@ctc.cc";
        $info->password = "123456";
        $info->android = $_SERVER['local_url'] . "/app/company/1/android";
        $info->ios = $_SERVER['local_url'] . "/app/company/1/ios";
        if (strtolower($lang) == 'fr') {
            $info->android_app = $_SERVER['local_url'] . "/imgs/common/google_fr_app";
            $info->ios_app = $_SERVER['local_url'] . "/imgs/common/ios_fr_app";
        } else {
            $info->android_app = $_SERVER['local_url'] . "/imgs/common/google_app";
            $info->ios_app = $_SERVER['local_url'] . "/imgs/common/ios_app";
        }

        \app('translator')->setLocale($lang);
        return view('re_set_pa_pwd_email', ["info" => $info, "type" => 2])->render();
    }

    public function newClientPwd($lang)
    {
        $info = Company::where("id", 1)
            ->first();
        $info->email = "ccav@ctc.cc";
        $info->password = "123456";
        $info->android = $_SERVER['local_url'] . "/app/company/1/android";
        $info->ios = $_SERVER['local_url'] . "/app/company/1/ios";
        if (strtolower($lang) == 'fr') {
            $info->android_app = $_SERVER['local_url'] . "/imgs/common/google_fr_app";
            $info->ios_app = $_SERVER['local_url'] . "/imgs/common/ios_fr_app";
        } else {
            $info->android_app = $_SERVER['local_url'] . "/imgs/common/google_app";
            $info->ios_app = $_SERVER['local_url'] . "/imgs/common/ios_app";
        }
        \app('translator')->setLocale($lang);
        return view('re_set_pa_pwd_email', ["info" => $info, "type" => 1])->render();
    }


    public function getCustomerBooking($bookingId, $lang)
    {
        $info = Booking::leftjoin("companies", "bookings.company_id", "=", "companies.id")
            ->leftjoin("company_settings","company_settings.company_id","=","companies.id")
            ->leftjoin("booking_transaction_histories as bth", "bth.booking_id", "=", "bookings.id")
            ->where('bookings.id', $bookingId)
            ->select(
                DB::raw(UrlSpell::getUrlSpell()->getCompaniesLogoByNameInDB("company_logo")),
                "bookings.company_id",
                "bookings.total_cost",
                "bookings.d_lat",
                "bookings.d_lng",
                "bookings.d_address",
                "bookings.a_address",
                "bookings.estimate_time",
                "bookings.estimate_distance",
                "bookings.car_data",
                "bookings.driver_data",
                "bookings.customer_data",
                "bookings.passenger_count",
                "bookings.bags_count",
                "bookings.type",
                "bookings.tva",
                "bth.ccy",
                "bookings.unit",
                "bookings.coupon_off",
                "companies.name as company_name",
                "companies.phone1 as company_number",
                "companies.email as company_email",
                "companies.email_host as email_host",
                "companies.email_port as email_port",
                "companies.email_password as email_password",
                "company_settings.distance_unit as com_unit",
                DB::raw("unix_timestamp(bookings.appointed_at) as appointed_at")
            )
            ->first();
        $info->customer_data = json_decode($info->customer_data);
        $info->car_data = json_decode($info->car_data);
        $info->driver_data = json_decode($info->driver_data);
        $d_address = json_decode($info->d_address) == null ? $info->d_address : json_decode($info->d_address)->formatted_address;
        $a_address = json_decode($info->a_address) == null ? $info->a_address : json_decode($info->a_address)->formatted_address;
        $info->d_address = $d_address;
        $info->a_address = $a_address;
        $info->estimate_time = MethodAlgorithm::formatTime($info->estimate_time);

//        $timeInfo = GeoLocationAlgorithm::getInstance()
//            ->getLocationTime($info->d_lat,$info->d_lng,$info->appointed_at);
        $info->timezone = "Asia/Shanghai";//$timeInfo->timeZoneId;
        $date = new KARLDateTime($info->appointed_at);
        $date->setTimezone(new \DateTimeZone($info->timezone));
        $date->setLanguage($lang);
        $info->appointed_at = $date;
        if (strtolower($lang) == 'fr') {
            $info->android_app = $_SERVER['local_url'] . "/imgs/common/google_fr_app";
            $info->ios_app = $_SERVER['local_url'] . "/imgs/common/ios_fr_app";
        } else {
            $info->android_app = $_SERVER['local_url'] . "/imgs/common/google_app";
            $info->ios_app = $_SERVER['local_url'] . "/imgs/common/ios_app";
        }

        \app('translator')->setLocale($lang);
        return view("customer_booking_email", ["info" => $info, "lang" => $lang])->render();
    }

    public function pushNotice()
    {
        $handler = New \App\Method\PushHandler($_SERVER['oaid'],$_SERVER['oarkey']);
        $handler->notify(["bb8cb02d-872c-4d05-8975-56a6556aab6a"],[
            "en"=>"123123"
        ]);
    }

    public function testCoupon($couponCode)
    {
        Stripe::setApiKey($_SERVER['STRIP_S_KEY']);
        $transfer = Coupon::retrieve($couponCode);
        return json_encode($transfer);
    }


    public function sendBackBookings($bookingId, $lang)
    {
        $booking = Booking::leftjoin("companies", "bookings.company_id", "=", "companies.id")
            ->where('bookings.id', $bookingId)
            ->select(
                DB::raw(UrlSpell::getUrlSpell()->getCompaniesLogoByNameInDB("company_logo")),
                "bookings.company_id",
                "bookings.total_cost",
                "bookings.d_lat",
                "bookings.d_lng",
                "bookings.d_address",
                "bookings.a_address",
                "bookings.estimate_time",
                "bookings.estimate_distance",
                "bookings.car_data",
                "bookings.driver_data",
                "bookings.customer_data",
                "bookings.passenger_count",
                "bookings.bags_count",
                "bookings.message",
                "bookings.type",
                DB::raw("unix_timestamp(bookings.appointed_at) as appointed_at")
            )
            ->first();

        $booking->customer_data = json_decode($booking->customer_data);
        $booking->car_data = json_decode($booking->car_data);
        $booking->driver_data = json_decode($booking->driver_data);
        $d_address = json_decode($booking->d_address) == null ? $booking->d_address : json_decode($booking->d_address)->formatted_address;
        $a_address = json_decode($booking->a_address) == null ? $booking->a_address : json_decode($booking->a_address)->formatted_address;
        $booking->d_address = $d_address;
        $booking->a_address = $a_address;
        $booking->estimate_time = MethodAlgorithm::formatTime($booking->estimate_time);
        $booking->timezone = "UTC";
        $date = new KARLDateTime($booking->appointed_at);
        $date->setTimezone(new \DateTimeZone($booking->timezone));
        $date->setLanguage($lang);
        $booking->appointed_at = $date;
        \app('translator')->setLocale($lang);
        return view('booking_email_send_back', ["booking" => $booking]);
    }

    public function addNewStripeClient()
    {
           $this->dispatch(new SendEmailResetSalePasswordJob('liqihai1987@gmail.com','123456'));
    }


    public function newSale($lang)
    {
        \app('translator')->setLocale($lang);
        return view('re_set_sl_pwd_email', ["saleId" => "CHN000001", "type" => 1,'pwd'=>'123456'])->render();
    }

    public function resetSalePwd($lang)
    {
        \app('translator')->setLocale($lang);
        return view('re_set_sl_pwd_email', ["saleId" => "CHN000001", "type" => 2,'pwd'=>'123456'])->render();
    }

    public function newAsst($lang)
    {
        \app('translator')->setLocale($lang);
        return view('re_set_as_pwd_email', ["asstId" => "SA2000", "type" => 1,'pwd'=>'123456'])->render();
    }

    public function resetAsstPwd($lang)
    {
        \app('translator')->setLocale($lang);
        return view('re_set_as_pwd_email', ["asstId" => "SA2000", "type" => 2,'pwd'=>'123456'])->render();
    }

    public function testStripe()
    {
        Stripe::setApiKey($_SERVER['STRIP_S_KEY']);
        return \Stripe\Customer::retrieve("cus_BOaDRBOgWUvkLe",["stripe_account"=>"acct_1B1mFaFKma2yyMDv"]);
//        $charge = Charge::create([
//            "amount" => 2003,
//            "application_fee" => 100,
//            "customer"=>"cus_BOaDRBOgWUvkLe",
//            "currency" => "usd",
//            "source"=>"card_1B1mpQIjfmQblAGIhLQyIvn3",
//            "metadata"=>[
//                "coupon"=>"",
//                "coupon_off"=>0
//            ]
//        ],["stripe_account"=>"acct_1B15zJIjfmQblAGI"]);
//        $charge = Charge::retrieve("ch_1B1V7dIjfmQblAGIVQaa2Xjx",["stripe_account"=>"acct_1B15zJIjfmQblAGI"]);
//        $charge->capture();
//        return $charge;
//        $blance = BalanceTransaction::retrieve("txn_1B1V7tIjfmQblAGI5A82va3I",["stripe_account"=>"acct_1B15zJIjfmQblAGI"]);
//        return $blance;
//        $refound = \Stripe\Refund::create(["charge"=>"ch_1B1PAeIjfmQblAGI4HcWUT3f","refund_application_fee"=>true],
//            ["stripe_account" => "acct_1B15zJIjfmQblAGI"]);
//        return $refound;
    }
}