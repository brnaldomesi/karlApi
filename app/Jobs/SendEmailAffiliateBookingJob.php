<?php

namespace App\Jobs;

use App\Method\KARLDateTime;
use App\Method\PushCenter;
use App\Method\UrlSpell;
use App\Method\MethodAlgorithm;
use App\Model\Admin;
use App\Model\Booking;
use App\Model\RunningError;
use App\QueueName;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Log;

class SendEmailAffiliateBookingJob extends Job
{
    /**
     * Create a new job instance.
     *
     * @return void
     */

    private $bookingId;

    public function __construct($bookingId)
    {
        $this->bookingId = $bookingId;
        $this->onQueue(QueueName::EmailAdminBooking);
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function work()
    {
        $booking = Booking::leftjoin("companies as own_com", "bookings.company_id", "=", "own_com.id")
            ->leftjoin("booking_transaction_histories as bth", "bth.booking_id", "=", "bookings.id")
            ->leftjoin("companies as exe_com", "bookings.exe_com_id", "=", "exe_com.id")
            ->leftjoin("company_settings as own_com_set","own_com_set.company_id","=","own_com.id")
            ->leftjoin("company_settings as exe_com_set","exe_com_set.company_id","=","exe_com.id")
            ->where("bookings.id", $this->bookingId)
            ->select(
                DB::raw(UrlSpell::getUrlSpell()->getCompaniesLogoByNameInDB("own_com_logo", "own_com")),
                DB::raw(UrlSpell::getUrlSpell()->getCompaniesLogoByNameInDB("exe_com_logo", "exe_com")),
                "own_com.name as own_com_name",
                "exe_com.name as exe_com_name",
                "own_com.email as own_com_email",
                "exe_com.email as exe_com_email",
                "own_com.phone1 as own_com_phone1",
                "exe_com.phone1 as exe_com_phone1",
                "own_com.phone2 as own_com_phone2",
                "exe_com.phone2 as exe_com_phone2",
                "own_com.timezone as own_com_timezone",
                "exe_com.timezone as exe_com_timezone",
                "exe_com.phone2 as exe_com_phone2",
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
                "bookings.unit",
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

        $booking->own_com_timezone = is_null($booking->own_com_timezone) ? "UTC" : $booking->own_com_timezone;
        $booking->exe_com_timezone = is_null($booking->exe_com_timezone) ? "UTC" : $booking->exe_com_timezone;

        $date = new KARLDateTime($booking->appointed_at);
        $booking->appointed_at = $date;

        $created_at = new KARLDateTime($booking->created);
        $booking->created = $created_at;

        $host = $_SERVER['SMTP_HOST'];
        $email = $_SERVER['SMTP_EMAIL'];
        $pwd = $_SERVER['SMTP_PWD'];
        $port = $_SERVER['SMTP_PORT'];
        $mail = $this->initPhpEmailBox($host, $email, $pwd, $port);

            // A
            try {
                PushCenter::initInstance()->sendAdminNotice("comNewBookingTitle", "comNewBooking", $booking->company_id);
            } catch (\Exception $ex) {
//                Log::error("",$ex);
            }
            $mail->setFrom($email, 'KARL Support');
            $mail->isHTML(true);
            $adminsFromA = Admin::leftjoin("users","users.id","=","admins.user_id")
                ->select("users.email","users.lang")
                ->where("users.company_id",$booking->company_id)
                ->get();
            foreach ($adminsFromA as $admin) {
                $mail->addAddress($admin->email);
                $booking->appointed_at->setTimezone(new \DateTimeZone($booking->own_com_timezone));
                $booking->appointed_at->setLanguage($admin->lang);
                $booking->created->setTimezone(new \DateTimeZone($booking->own_com_timezone));
                $booking->created->setLanguage($admin->lang);
                $booking->an_type = 1;
                \app('translator')->setLocale($admin->lang);
                $mail->Subject = Lang::get("booking.newBooking");
                $viewA = view("create_booking_email", ["booking" => $booking,"lang"=>$admin->lang])->render();
                $mail->msgHTML($viewA);
                if (!$mail->send()) {
                    Log::info("SEND EMAIL TO COMPANY A " . $mail->ErrorInfo);
                } else {
                    RunningError::recordRunningError(
                        RunningError::TYPE_EMAIL,
                        RunningError::STATE_SUCCESS,
                        "Successfully send mail to the own company."
                    );
                }

                $mail->clearAddresses();
            }

            // B

            try {
                PushCenter::initInstance()->sendAdminNotice("comNewBookingTitle", "comNewBooking", $booking->exe_com_id);
            } catch (\Exception $ex) {
//                Log::error("",$ex);
            }
            $adminsFromB =Admin::leftjoin("users","users.id","=","admins.user_id")
                ->select("users.email","users.lang")
                ->where("users.company_id",$booking->exe_com_id)
                ->get();
            $booking->com_unit = $booking->exe_unit;
            foreach ($adminsFromB as $admin) {
                $mail->addAddress($admin->email);
                $booking->appointed_at->setTimezone(new \DateTimeZone($booking->exe_com_timezone));
                $booking->appointed_at->setLanguage($admin->lang);
                $booking->created->setTimezone(new \DateTimeZone($booking->exe_com_timezone));
                $booking->created->setLanguage($admin->lang);
                $booking->an_type = 2;
                \app('translator')->setLocale($admin->lang);
                $viewB = view("create_booking_email", ["booking" => $booking,"lang"=>$admin->lang])->render();
                $mail->Subject = Lang::get("booking.newBooking");
                $mail->msgHTML($viewB);
                if (!$mail->send()) {
                    Log::info("SEND EMAIL TO COMPANY B " . $mail->ErrorInfo);
                } else {
                    RunningError::recordRunningError(
                        RunningError::TYPE_EMAIL,
                        RunningError::STATE_SUCCESS,
                        "Successfully send mail to the affiliate company."
                    );
                }
                $mail->clearAddresses();
            }

    }
}
