<?php

namespace App\Jobs;

use App\Method\GeoLocationAlgorithm;
use App\Method\KARLDateTime;
use App\Method\MethodAlgorithm;
use App\Method\UrlSpell;
use App\Model\Booking;
use App\Model\Offer;
use App\Model\RunningError;
use App\QueueName;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Log;

class SendEmailCustomerBookingEditJob extends Job
{
    /**
     * Create a new job instance.
     *
     * @return void
     */

    private $bookingId;

    public function __construct($bookingId)
    {
        //
        $this->bookingId = $bookingId;
        $this->onQueue(QueueName::EmailCustomerBookingUpdate);
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function work()
    {
        //
        $info = Booking::leftjoin("companies","bookings.company_id","=","companies.id")
            ->leftjoin("company_settings","company_settings.company_id","=","companies.id")
            ->leftjoin("customers","customers.id","=","bookings.customer_id")
            ->leftjoin("users","customers.user_id","=","users.id")
            ->leftjoin("booking_transaction_histories as bth","bth.booking_id","=","bookings.id")
            ->where('bookings.id', $this -> bookingId)
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
                "bookings.unit",
                "bth.ccy",
                "users.lang",
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
        $d_address = json_decode($info->d_address)==null?$info->d_address:json_decode($info->d_address)->formatted_address;
        $a_address = json_decode($info->a_address)==null?$info->a_address:json_decode($info->a_address)->formatted_address;
        $info->d_address = $d_address;
        $info->a_address = $a_address;
        $info->estimate_time = MethodAlgorithm::formatTime($info->estimate_time);

        $timeInfo = GeoLocationAlgorithm::getInstance()
            ->getLocationTime($info->d_lat,$info->d_lng,$info->appointed_at);
        $info->timezone = isset($timeInfo->timeZoneId)?$timeInfo->timeZoneId:"UTC";

        $date = new KARLDateTime($info->appointed_at);
        $date->setTimezone(new \DateTimeZone($info->timezone));
        $date->setLanguage($info->lang);
        $info->appointed_at=$date;
        $updateDate = new KARLDateTime(time());
        $updateDate->setTimezone(new \DateTimeZone($info->timezone));
        $updateDate->setLanguage($info->lang);
        if(strtolower($info->lang) == 'fr'){
            $info->android_app = $_SERVER['local_url']."/imgs/common/google_fr_app";
            $info->ios_app = $_SERVER['local_url']."/imgs/common/ios_fr_app";
        }else{
            $info->android_app = $_SERVER['local_url']."/imgs/common/google_app";
            $info->ios_app = $_SERVER['local_url']."/imgs/common/ios_app";
        }

        \app('translator')->setLocale($info->lang);
        $view = view("customer_booking_email", ["info" =>$info,"lang"=>$info->lang])->render();

        $customer = $info->customer_data;

        $host = $info->email_host;
        $email = $info->company_email;
        $pwd = base64_decode($info->email_password);
        $port = $info->email_port;
        if (empty($host) || empty($email) || empty($pwd) || empty($port))
        {
            $host = $_SERVER['SMTP_HOST'];
            $email = $_SERVER['SMTP_EMAIL'];
            $pwd = $_SERVER['SMTP_PWD'];
            $port = $_SERVER['SMTP_PORT'];
        }

        $mail = $this->initPhpEmailBox($host, $email, $pwd, $port );
        try {
            $mail->setFrom($email, $info->company_name.' Support');
            $mail->Subject = Lang::get("booking.updateTitle",["company"=>$info->company_name,"time"=>$updateDate->getDate()]);
            $mail->addAddress($customer->email);
            $mail->isHTML(true);
            $mail->msgHTML($view);
            if (!$mail->send()) {
                Log::error($mail->ErrorInfo);
            } else {
                RunningError::recordRunningError(
                    RunningError::TYPE_EMAIL,
                    RunningError::STATE_SUCCESS,
                    "send customer success"
                );
            }
        } catch (\Exception $ex) {
            Log::error($ex);
        }
    }
}
