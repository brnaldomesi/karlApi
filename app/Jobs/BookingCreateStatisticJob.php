<?php

namespace App\Jobs;

use App\Constants;
use App\Method\MethodAlgorithm;
use App\Method\PaymentMethod;
use App\Model\Booking;
use App\Model\BookingDayStatistic;
use App\QueueName;
use Illuminate\Support\Facades\DB;

/**
 * MARK STAT
 * Class BookingStatisticJob
 * @package App\Jobs
 */
class BookingCreateStatisticJob extends Job
{

    private $bookingId;

    /**
     * BookingStatisticJob constructor.
     * @param $bookingId
     */
    public function __construct($bookingId)
    {
        $this->bookingId = $bookingId;
        $this->onQueue(QueueName::BookingCreateStatistic);
    }


    /**
     * Execute the job.
     *
     * @return void
     */
    public function work()
    {
        //
        $booking = Booking::leftjoin('companies as own_com', 'own_com.id', '=', 'bookings.company_id')
            ->leftjoin('companies as exe_com', 'exe_com.id', '=', 'bookings.exe_com_id')
            ->where('bookings.id', $this->bookingId)
            ->select(
                'bookings.exe_com_id',
                'bookings.company_id',
                'bookings.total_cost',
                'bookings.type',
                'bookings.tva',
                'bookings.coupon_off',
                DB::raw("unix_timestamp(appointed_at) as appointed_at"),
                "own_com.rate as own_rate",
                "own_com.ccy as own_ccy",
                "own_com.timezone as own_timezone",
                "exe_com.ccy as exe_ccy",
                "exe_com.timezone as exe_timezone"
            )
            ->first();
        \Log::info('booking info is ' . $booking);
        {
            $timeZone = new \DateTimeZone($_SERVER['TIME_ZONE']);
            $statDay = new \DateTime("@{$booking->appointed_at}");
            $statDay->setTimezone($timeZone);
            $settleDayString = $statDay->format('Y-m-d');
            $dayTimestamp = strtotime($settleDayString);
            $offset = $statDay->format('Z');
            $gmtDayTimestamp = $dayTimestamp - $offset;
            $gmtDayTime = MethodAlgorithm::formatTimestampToDate($gmtDayTimestamp);
            $stat_week = $statDay->format('W');
            $stat_month = $statDay->format('n');
            $stat_year = $statDay->format('Y');
            $stat_week_year = ($stat_month == 1 && $stat_week > 50) ? ($stat_year - 1) : $stat_year;
            $statistic = BookingDayStatistic::
            firstOrCreate([
                'company_id' => 0,
                "stat_date" => $gmtDayTime,
                "stat_day" => $statDay->format('z'),
                "stat_month" => $statDay->format('n'),
                "stat_year" => $statDay->format('Y'),
                "stat_week" => $statDay->format('W'),
                "stat_week_year" => $stat_week_year,
            ]);

            \Log::info('booking is ' . $booking . " stat is " . $statistic);
            $statistic->total_bookings += 1;
            $statistic->an_count += ($booking->exe_com_id == $booking->company_id ? 0 : 1);
            if ($booking->type == Booking::CHECK_TYPE_DISTANCE) {
                $statistic->p2p_count += 1;
            } else if ($booking->type == Booking::CHECK_TYPE_HOURLY) {
                $statistic->hour_count += 1;
            } else if ($booking->type == Booking::CHECK_TYPE_CUSTOM) {
                $statistic->cq_count += 1;
            }
            $statistic->save();
        }

        if ($booking->exe_com_id == $booking->company_id) {
            $totalPrice =  ($booking->total_cost-round($booking->coupon_off*(1+$booking->tva/100),2));
            $platePrice = round($totalPrice*$booking->own_rate,2);
            $aPrice = $totalPrice-$platePrice;


            $timeZone = new \DateTimeZone($booking->own_timezone);
            $statDay = new \DateTime("@{$booking->appointed_at}");
            $statDay->setTimezone($timeZone);
            $settleDayString = $statDay->format('Y-m-d');
            $dayTimestamp = strtotime($settleDayString);
            $offset = $statDay->format('Z');
            $gmtDayTimestamp = $dayTimestamp - $offset;
            $gmtDayTime = MethodAlgorithm::formatTimestampToDate($gmtDayTimestamp);
            $statistic = BookingDayStatistic::
            firstOrCreate([
                'company_id' => $booking->company_id,
                "stat_date" => $gmtDayTime,
                "stat_day" => $statDay->format('z'),
                "stat_month" => $statDay->format('n'),
                "stat_year" => $statDay->format('Y'),
                "stat_week" => $statDay->format('W'),
                "stat_week_year" => $stat_week_year
            ]);

            $statistic->total_bookings += 1;
            if ($booking->type == Booking::CHECK_TYPE_DISTANCE) {
                $statistic->p2p_count += 1;
            } else if ($booking->type == Booking::CHECK_TYPE_HOURLY) {
                $statistic->hour_count += 1;
            } else if ($booking->type == Booking::CHECK_TYPE_CUSTOM) {
                $statistic->cq_count += 1;
            }
            $statistic->total_est_amount += $aPrice;
            $statistic->save();
        } else {
            {
                $totalPrice =  ($booking->total_cost);
                $platePrice = round($totalPrice*$booking->own_rate,2);
                $aPrice = $totalPrice-$platePrice;
                $tvaFee = round($totalPrice / (1 + $booking->tva / 100) * ($booking->tva / 100), 2);
                //an费用等于剩余费用出去税费的%85 再加上税费
                $bPrice = round(($totalPrice - $tvaFee) * Constants::EXE_COMPANY_TVA, 2) + $tvaFee;
                $aPrice  = $aPrice - $bPrice;

                $aPrice = PaymentMethod::ccyCvt($booking->exe_ccy,$booking->exe_ccy,$aPrice);

                $exeTimezone = new \DateTimeZone($booking->exe_timezone);
                $exeSettleDay = new \DateTime("@{$booking->appointed_at}");
                $exeSettleDay->setTimezone($exeTimezone);
                $settleDayString = $exeSettleDay->format('Y-m-d');
                $dayTimestamp = strtotime($settleDayString);
                $offset = $exeSettleDay->format('Z');
                $gmtDayTimestamp = $dayTimestamp - $offset;
                $gmtDayTime = MethodAlgorithm::formatTimestampToDate($gmtDayTimestamp);
                \Log::info('gmt time timestamp is ' . $gmtDayTime);
                $exe_stat_week = $exeSettleDay->format('W');
                $exe_stat_month = $exeSettleDay->format('n');
                $exe_stat_year = $exeSettleDay->format('Y');
                $stat_week_year = ($exe_stat_month == 1 && $exe_stat_week > 50) ? ($exe_stat_year - 1) : $exe_stat_year;
                $exeStat = BookingDayStatistic::firstOrCreate([
                    'company_id' => $booking->exe_com_id,
                    "stat_date" => $gmtDayTime,
                    "stat_day" => $exeSettleDay->format('z'),
                    "stat_month" => $exeSettleDay->format('n'),
                    "stat_year" => $exeSettleDay->format('Y'),
                    "stat_week" => $exeSettleDay->format('W'),
                    "stat_week_year" => $stat_week_year,
                ]);
                \Log::info("exe stat" . $exeStat);
                $exeStat->an_count += 1;                    //an总计+1
                $exeStat->exe_an_count += 1;                //B单 +1

                if ($booking->type == Booking::CHECK_TYPE_DISTANCE) {
                    $exeStat->p2p_count += 1;
                } else if ($booking->type == Booking::CHECK_TYPE_HOURLY) {
                    $exeStat->hour_count += 1;
                } else if ($booking->type == Booking::CHECK_TYPE_CUSTOM) {
                    $exeStat->cq_count += 1;
                }
                $exeStat->total_est_amount += $bPrice;

                $exeStat->save();
            }
            {
                $ownTimezone = new \DateTimeZone($booking->own_timezone);
                $ownSettleDay = new \DateTime("@{$booking->appointed_at}");
                $ownSettleDay->setTimezone($ownTimezone);
                $settleDayString = $ownSettleDay->format('Y-m-d');
                $dayTimestamp = strtotime($settleDayString);
                $offset = $ownSettleDay->format('Z');
                $gmtDayTimestamp = $dayTimestamp - $offset;
                $gmtDayTime = MethodAlgorithm::formatTimestampToDate($gmtDayTimestamp);
                \Log::info('gmt time timestamp is ' . $gmtDayTime);

                $own_stat_week = $ownSettleDay->format('W');
                $own_stat_month = $ownSettleDay->format('n');
                $own_stat_year = $ownSettleDay->format('Y');
                $stat_week_year = ($own_stat_month == 1 && $own_stat_week > 50) ?
                    ($own_stat_year - 1) : $own_stat_year;
                $ownStat = BookingDayStatistic::firstOrCreate([
                    'company_id' => $booking->company_id,
                    "stat_date" => $gmtDayTime,
                    "stat_day" => $ownSettleDay->format('z'),
                    "stat_month" => $ownSettleDay->format('n'),
                    "stat_year" => $ownSettleDay->format('Y'),
                    "stat_week" => $ownSettleDay->format('W'),
                    "stat_week_year" => $stat_week_year,
                ]);
                \Log::info("own stat" . $ownStat);

                $ownStat->total_bookings += 1;               //总单数+1
                $ownStat->an_count += 1;                    //an总计+1
                $ownStat->out_an_count += 1;                //A单 +1
                if ($booking->type == Booking::CHECK_TYPE_DISTANCE) {
                    $ownStat->p2p_count += 1;
                } else if ($booking->type == Booking::CHECK_TYPE_HOURLY) {
                    $ownStat->hour_count += 1;
                } else if ($booking->type == Booking::CHECK_TYPE_CUSTOM) {
                    $ownStat->cq_count += 1;
                }
                $ownStat->total_est_amount += $aPrice;
                $ownStat->save();
            }
        }
    }

}
