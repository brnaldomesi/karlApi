<?php

namespace App\Jobs;

use App\Method\GeoLocationAlgorithm;
use App\Method\KARLDateTime;
use App\Method\PushCenter;
use App\Method\UrlSpell;
use App\Method\MethodAlgorithm;
use App\Model\Admin;
use App\Model\Booking;
use App\Model\Company;
use App\Model\RunningError;
use App\QueueName;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Log;

class SendEmailAdminBookingSendBackJob extends Job
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
        $this->onQueue(QueueName::EmailAdminBookingSendBack);
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function work()
    {
        //
        $booking = Booking::leftjoin("companies","bookings.company_id","=","companies.id")
            ->leftjoin("company_settings","company_settings.company_id","=","companies.id")
            ->where('bookings.id',$this->bookingId)
            ->select(
                DB::raw(UrlSpell::getUrlSpell()->getCompaniesLogoByNameInDB("company_logo")),
                "bookings.company_id",
                "bookings.total_cost",
                "bookings.d_lat",
                "bookings.d_lng",
                "bookings.a_lat",
                "bookings.a_lng",
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
                "company_settings.distance_unit as com_unit",
                DB::raw("unix_timestamp(bookings.appointed_at) as appointed_at")
            )
            ->first();

        PushCenter::initInstance()->sendAdminNotice("anNoticeTitle", "anNotice", $booking->company_id);
        $booking->customer_data = json_decode($booking->customer_data);
        $booking->car_data = json_decode($booking->car_data);
        $booking->driver_data = json_decode($booking->driver_data);
        $d_address = json_decode($booking->d_address)==null?$booking->d_address:json_decode($booking->d_address)->formatted_address;
        $a_address = json_decode($booking->a_address)==null?$booking->a_address:json_decode($booking->a_address)->formatted_address;
        $booking->d_address = $d_address;
        $booking->a_address = $a_address;
        $booking->estimate_time = MethodAlgorithm::formatTime($booking->estimate_time);

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

        $date = new KARLDateTime($booking->appointed_at);

        $date->setTimezone(new \DateTimeZone($timezone));

        $booking->appointed_at = $date;
        $admins = Admin::leftjoin("users","admins.user_id","=","users.id")
            ->where("users.company_id",$booking->company_id)
            ->select("users.email","users.lang")
            ->get();
        $host = $_SERVER['SMTP_HOST'];
        $email = $_SERVER['SMTP_EMAIL'];
        $pwd = $_SERVER['SMTP_PWD'];
        $port = $_SERVER['SMTP_PORT'];
        $mail = $this->initPhpEmailBox($host, $email, $pwd, $port);

        foreach ($admins as $admin) {

            try {
                $booking->appointed_at->setLanguage($admin->lang);
                \app('translator')->setLocale($admin->lang);
                $view = view("booking_email_send_back", ["booking" =>$booking,"lang"=>$admin->lang])->render();
                $mail->setFrom($email, 'KARL Support');
                $title = Lang::get("booking.sendBackTitle");
                $mail->Subject = $title;
                $mail->addAddress($admin->email);
                $mail->isHTML(true);
                $mail->msgHTML($view);
                if (!$mail->send()) {
                    Log::info("SEND EMAIL " . $mail->ErrorInfo);
                } else {
                    RunningError::recordRunningError(
                        RunningError::TYPE_EMAIL,
                        RunningError::STATE_SUCCESS,
                        "send admin success"
                    );
                }
            } catch (\Exception $ex) {
                Log::info($ex->getMessage());
            }
            $mail->clearAddresses();
        }



    }
}
