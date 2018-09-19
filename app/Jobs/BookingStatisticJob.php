<?php

namespace App\Jobs;

use App\Method\PaymentMethod;
use App\Model\Bill;
use App\Model\Booking;
use App\Model\BookingDayStatistic;
use App\Model\Offer;
use App\QueueName;
use Illuminate\Support\Facades\Log;
use PayPal\Api\Payment;

/**
 * MARK STAT
 * Class BookingStatisticJob
 * @package App\Jobs
 */
class BookingStatisticJob extends Job
{

    private $bookingId;

    /**
     * BookingStatisticJob constructor.
     * @param $bookingId
     */
    public function __construct($bookingId)
    {
        $this->bookingId = $bookingId;
        $this->onQueue(QueueName::BookingStatistic);
    }


    /**
     * Execute the job.
     *
     * @return void
     */
    public function work()
    {
        //
        $bill = Bill::leftjoin("bookings", "bills.booking_id", "=", "bookings.id")
            ->leftjoin("orders", "bookings.id", "=", "orders.booking_id")
            ->leftjoin("companies as exe_com", "bookings.exe_com_id", "=", "exe_com.id")
            ->leftjoin("companies as own_com", "bookings.company_id", "=", "own_com.id")
            ->where('bills.booking_id',$this->bookingId)
            ->select(
                \DB::raw("CASE WHEN orders.start_time <= bookings.appointed_at
                        THEN  1
                        ELSE 0
                        END as on_time"),
                \DB::raw("unix_timestamp(bookings.appointed_at) as settle_time"),
                "bills.com_income",
                "bills.an_fee",
                "bills.platform_income",
                "bookings.exe_com_id",
                "bookings.company_id",
                "bookings.type",
                "own_com.ccy as own_ccy",
                "own_com.timezone as own_timezone",
                "exe_com.ccy as exe_ccy",
                "exe_com.timezone as exe_timezone"
            )->first();
        if(empty($bill)){
            Log::info('bill not exit');
            return;
        }
        if ($bill->exe_com_id == $bill->company_id) {
            $timeZone = new \DateTimeZone($bill->own_timezone);
            $settleDay = new \DateTime("@{$bill->settle_time}");
            $settleDay->setTimezone($timeZone);
            $statistic = BookingDayStatistic::where('company_id', $bill->company_id)
                ->where([
                    "stat_day" => $settleDay->format('z'),
                    "stat_month" => $settleDay->format('n'),
                    "stat_year" => $settleDay->format('Y'),
                    "stat_week" => $settleDay->format('W')
                ])
                ->first();
            \Log::info("STAT IS ".$statistic);
            $statistic->completed_bookings += 1;
            $statistic->on_time += $bill->on_time;
            $statistic->appearance_count += $bill->appearance;
            $statistic->professionalism_count += $bill->professionalism;
            $statistic->cleanliness_count += $bill->cleanliness;
            $statistic->quality_count += $bill->quality;
            $statistic->total_income += $bill->com_income;
            $statistic->total_plate += $bill->platform_income;
            if ($bill->type == Booking::CHECK_TYPE_DISTANCE) {
                $statistic->p2p_count += 1;
            } else if ($bill->type == Booking::CHECK_TYPE_HOURLY) {
                $statistic->hour_count += 1;
            } else if ($bill->type == Booking::CHECK_TYPE_CUSTOM) {
                $statistic->cq_count += 1;
            }
            $statistic->save();
        } else {
            {
                $exeTimezone = new \DateTimeZone($bill->exe_timezone);
                $exeSettleDay = new \DateTime("@{$bill->settle_time}");
                $exeSettleDay->setTimezone($exeTimezone);
                $exeStat = BookingDayStatistic::where('company_id', $bill->exe_com_id)
                    ->where([
                        "stat_day" => $exeSettleDay->format('z'),
                        "stat_month" => $exeSettleDay->format('n'),
                        "stat_year" => $exeSettleDay->format('Y'),
                        "stat_week" => $exeSettleDay->format('W')
                    ])
                    ->first();
                \Log::info("EXE STAT IS ".$exeStat);
                $exeStat->completed_bookings += 1;               //总单数+1
                $exeStat->on_time += $bill->on_time;
                $exeStat->appearance_count += $bill->appearance;
                $exeStat->professionalism_count += $bill->professionalism;
                $exeStat->cleanliness_count += $bill->cleanliness;
                $exeStat->quality_count += $bill->quality;
                $exeStat->total_income += $bill->an_fee;    //an 费用收入

                if ($bill->type == Booking::CHECK_TYPE_DISTANCE) {
                    $exeStat->p2p_count += 1;
                } else if ($bill->type == Booking::CHECK_TYPE_HOURLY) {
                    $exeStat->hour_count += 1;
                } else if ($bill->type == Booking::CHECK_TYPE_CUSTOM) {
                    $exeStat->cq_count += 1;
                }

                $exeStat->save();
            }


            {
                $ownTimezone = new \DateTimeZone($bill->own_timezone);
                $ownSettleDay = new \DateTime("@{$bill->settle_time}");
                $ownSettleDay->setTimezone($ownTimezone);
                $ownStat = BookingDayStatistic::where('company_id', $bill->company_id)
                    ->where([
                        "stat_day" => $ownSettleDay->format('z'),
                        "stat_month" => $ownSettleDay->format('n'),
                        "stat_year" => $ownSettleDay->format('Y'),
                        "stat_week" => $ownSettleDay->format('W')
                    ])
                    ->first();
                \Log::info("OWN STAT IS ".$ownStat);
                $ownStat->completed_bookings += 1;               //总单数+1
                $ownStat->on_time += $bill->on_time;
                $ownStat->total_income += PaymentMethod::ccyCvt($bill->exe_ccy,$bill->own_ccy,$bill->com_income);    //公司收入
                $ownStat->total_plate += $bill->platform_income;    //平台收入
//                $ownStat->total_an_fee += $bill->an_fee;        //an支出
                if ($bill->type == Booking::CHECK_TYPE_DISTANCE) {
                    $ownStat->p2p_count += 1;
                } else if ($bill->type == Booking::CHECK_TYPE_HOURLY) {
                    $ownStat->hour_count += 1;
                } else if ($bill->type == Booking::CHECK_TYPE_CUSTOM) {
                    $ownStat->cq_count += 1;
                }
                $ownStat->save();
            }
        }

    }
}
