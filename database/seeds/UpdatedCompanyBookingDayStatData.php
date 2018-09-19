<?php
use App\Method\MethodAlgorithm;
use App\Model\Bill;
use App\Model\Booking;
use App\Model\BookingDayStatistic;
use App\Model\Offer;
use Illuminate\Support\Facades\DB;

/**
 * Mark STAT
 * Created by PhpStorm.
 * User: liqihai
 * Date: 16/9/19
 * Time: 下午3:31
 */
class UpdatedCompanyBookingDayStatData extends BaseSeeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    protected function task()
    {
        DB::transaction(function () {
            echo "begin delete booking stat \n";

            DB::table('booking_day_statistics')->truncate();
            echo "finish delete booking stat \n";

            $companyCount = \App\Model\Company::count();
            $maxDate = Booking::select(
                DB::raw("case when unix_timestamp(max(appointed_at)) < unix_timestamp(now())
                            THEN unix_timestamp(now())
                            else unix_timestamp(max(appointed_at)) 
                            end   as appointed_at
                "),
                DB::raw("unix_timestamp(min(appointed_at)) as min_time")
            )->first();
            echo "total {$companyCount} companies \n";
            MethodAlgorithm::createStatDayForCompany(0,1467356400,$maxDate->appointed_at,$_SERVER['TIME_ZONE']);
            for ($j = 0; $j <= $companyCount / 10; $j++) {
                $companies = \App\Model\Company::
                select('companies.id',
                    'companies.timezone',
                    DB::raw('unix_timestamp(companies.created_at) as created'))
                    ->orderBy('companies.created_at', 'asc')
                    ->skip(10 * $j)
                    ->take(10)
                    ->get();

                echo "finish check companies " . (10 * ($j + 1)) . " \n";
                foreach ($companies as $company) {
                    MethodAlgorithm
                        ::createStatDayForCompany($company->id, $company->created,
                            $maxDate->appointed_at, $company->timezone);
                }
            }

            $count = Booking::count();
            echo "total {$count} bookings \n";
            for ($i = 0; $i <= $count / 20; $i++) {
                $bookings = Booking::leftjoin("companies as exe_com", "bookings.exe_com_id", "=", "exe_com.id")
                    ->leftjoin("companies as own_com", "bookings.company_id", "=", "own_com.id")
                    ->select(
                        DB::raw("unix_timestamp(bookings.appointed_at) as appointed_at"),
                        "bookings.exe_com_id",
                        "bookings.company_id",
                        "bookings.total_cost",
                        "bookings.type",
                        "own_com.timezone as own_timezone",
                        "exe_com.timezone as exe_timezone",
                        DB::raw(" case when bookings.type=" . Booking::CHECK_TYPE_DISTANCE . " 
                                then 1 
                                else 0
                                end as p2p_count"),
                        DB::raw(" case when bookings.type=" . Booking::CHECK_TYPE_HOURLY . " 
                                then 1 
                                else 0
                                end as hour_count"),
                        DB::raw(" case when bookings.type=" . Booking::CHECK_TYPE_CUSTOM . " 
                                then 1 
                                else 0
                                end as cq_count")
                    )
                    ->skip(20 * $i)
                    ->take(20)
                    ->get();
                echo "finish check bookings " . (20 * ($i + 1)) . "\n";

                foreach ($bookings as $booking) {
                    {
                        $platTimeZone = new \DateTimeZone($_SERVER['TIME_ZONE']);
                        $platStatDay = new \DateTime("@{$booking->appointed_at}");
                        $platStatDay->setTimezone($platTimeZone);
                        BookingDayStatistic::where('company_id', 0)
                            ->where([
                                "stat_day" => $platStatDay->format('z'),
                                "stat_month" => $platStatDay->format('n'),
                                "stat_year" => $platStatDay->format('Y'),
                                "stat_week" => $platStatDay->format('W')
                            ])
                            ->update([
                                'total_bookings'=>DB::raw("total_bookings+1"),
                                'an_count'=>DB::raw("an_count+".($booking->exe_com_id == $booking->company_id?0:1)),
                                'p2p_count'=>DB::raw("p2p_count+".$booking->p2p_count),
                                'hour_count'=>DB::raw("hour_count+".$booking->hour_count),
                                'cq_count'=>DB::raw("cq_count+".$booking->cq_count),
                            ]);
                    }
                    if ($booking->exe_com_id == $booking->company_id) {
                        $timeZone = new \DateTimeZone($booking->own_timezone);
                        $statDay = new \DateTime("@{$booking->appointed_at}");
                        $statDay->setTimezone($timeZone);
                        \App\Model\BookingDayStatistic::where('company_id', $booking->company_id)
                            ->where([
                                "stat_day" => $statDay->format('z'),
                                "stat_month" => $statDay->format('n'),
                                "stat_year" => $statDay->format('Y'),
                                "stat_week" => $statDay->format('W')
                            ])
                            ->update([
                                'total_bookings'=>DB::raw("total_bookings+1"),
                                'p2p_count'=>DB::raw("p2p_count+".$booking->p2p_count),
                                'hour_count'=>DB::raw("hour_count+".$booking->hour_count),
                                'cq_count'=>DB::raw("cq_count+".$booking->cq_count),
                                'total_est_amount'=>DB::raw("total_est_amount+".$booking->total_cost)
                        ]);
                    } else {
                        {
                            if (is_null($booking->exe_timezone)) {
                                continue;
                            }
                            $exeTimezone = new \DateTimeZone($booking->exe_timezone);
                            $exeSettleDay = new \DateTime("@{$booking->appointed_at}");
                            $exeSettleDay->setTimezone($exeTimezone);
                            BookingDayStatistic::where('company_id', $booking->exe_com_id)
                                ->where([
                                    "stat_day" => $exeSettleDay->format('z'),
                                    "stat_month" => $exeSettleDay->format('n'),
                                    "stat_year" => $exeSettleDay->format('Y'),
                                    "stat_week" => $exeSettleDay->format('W')
                                ])
                                ->update(
                                    [
                                        'an_count'=>DB::raw("an_count+1"),
                                        'exe_an_count'=>DB::raw("exe_an_count+1"),
                                        'p2p_count'=>DB::raw("hour_count+".$booking->p2p_count),
                                        'hour_count'=>DB::raw("hour_count+".$booking->hour_count),
                                        'cq_count'=>DB::raw("cq_count+".$booking->cq_count),
                                    ]
                                );
                        }


                        {

                            if (is_null($booking->own_timezone)) {
                                continue;
                            }
                            $ownTimezone = new \DateTimeZone($booking->own_timezone);
                            $ownSettleDay = new \DateTime("@{$booking->appointed_at}");
                            $ownSettleDay->setTimezone($ownTimezone);
                            BookingDayStatistic::where('company_id', $booking->company_id)
                                ->where([
                                    "stat_day" => $ownSettleDay->format('z'),
                                    "stat_month" => $ownSettleDay->format('n'),
                                    "stat_year" => $ownSettleDay->format('Y'),
                                    "stat_week" => $ownSettleDay->format('W')
                                ])
                                ->update(
                                    [
                                        'total_bookings'=>DB::raw("total_bookings+1"),          //总单数+1
                                        'an_count'=>DB::raw("an_count+1"),          //an总计+1
                                        'out_an_count'=>DB::raw("out_an_count+1"),          //A单 +1
                                        'p2p_count'=>DB::raw("hour_count+".$booking->p2p_count),
                                        'hour_count'=>DB::raw("hour_count+".$booking->hour_count),
                                        'cq_count'=>DB::raw("cq_count+".$booking->cq_count),
                                        'total_est_amount'=>DB::raw("total_est_amount+".$booking->total_cost)
                                    ]
                                );
                        }
                    }
                }
            }
            $billCount = Bill::count();
            echo "total {$billCount} bills \n";
            for ($i = 0; $i <= $billCount / 20; $i++) {
                $bills = Bill::leftjoin("bookings", "bills.booking_id", "=", "bookings.id")
                    ->leftjoin("orders", "bookings.id", "=", "orders.booking_id")
                    ->leftjoin("feedbacks", "orders.id", "=", "feedbacks.order_id")
                    ->leftjoin("companies as exe_com", "bookings.exe_com_id", "=", "exe_com.id")
                    ->leftjoin("companies as own_com", "bookings.company_id", "=", "own_com.id")
                    ->select(
                        DB::raw("CASE WHEN orders.start_time <= bookings.appointed_at
                                    THEN  1
                                    ELSE 0
                                    END as on_time"),
                        DB::raw("unix_timestamp(bookings.appointed_at) as settle_time"),
                        DB::raw("case when orders.order_state in (5,6,7) 
                           then 1
                           else 0
                           end as canceled"),
                        DB::raw("case when orders.order_state = 8 
                           then 1
                           else 0
                           end as invalid"),
                        DB::raw("case when orders.order_state = 3 
                           then 1
                           else 0
                           end as trouble"),
                        "bills.com_income",
                        "bills.an_fee",
                        "bills.platform_income",
                        "bookings.exe_com_id",
                        "bookings.company_id",
                        "bookings.type",
                        "own_com.timezone as own_timezone",
                        "exe_com.timezone as exe_timezone"
//                        "feedbacks.appearance",
//                        "feedbacks.professionalism",
//                        "feedbacks.driving_ability",
//                        "feedbacks.cleanliness",
//                        "feedbacks.quality"
                    )
                    ->skip(20 * $i)
                    ->take(20)
                    ->get();
                echo "finish check bills " . (20 * $i + 1) . " \n";

                foreach ($bills as $bill) {
                    {
                        $timeZone = new \DateTimeZone($_SERVER['TIME_ZONE']);
                        $statDay = new \DateTime("@{$bill->settle_time}");
                        $statDay->setTimezone($timeZone);
                        BookingDayStatistic::where('company_id',0)
                            ->where([
                                "stat_day" => $statDay->format('z'),
                                "stat_month" => $statDay->format('n'),
                                "stat_year" => $statDay->format('Y'),
                                "stat_week" => $statDay->format('W')
                            ])
                            ->update([
                                "completed_bookings" => DB::raw("completed_bookings+1"),
                                "on_time" => DB::raw("on_time+" . $bill->on_time),
                                "cancel_count" => DB::raw("cancel_count+" . $bill->canceled),
                                "invalid_count" => DB::raw("invalid_count+" . $bill->invalid),
                                "trouble_count" => DB::raw("trouble_count+" . $bill->trouble),
//                                "appearance_count" => DB::raw("appearance_count+" . $bill->appearance),
//                                "professionalism_count" => DB::raw("professionalism_count+" . $bill->professionalism),
//                                "cleanliness_count" => DB::raw("cleanliness_count+" . $bill->cleanliness),
//                                "quality_count" => DB::raw("quality_count+" . $bill->quality),
                                "total_income" => DB::raw("total_income+" . $bill->platform_income),
                            ]);
                    }

                    if ($bill->exe_com_id == $bill->company_id) {
                        if (is_null($bill->exe_timezone)) {
                            continue;
                        }

                        $timeZone = new \DateTimeZone($bill->own_timezone);
                        $statDay = new \DateTime("@{$bill->settle_time}");
                        $statDay->setTimezone($timeZone);
                        BookingDayStatistic::where('company_id', $bill->company_id)
                            ->where([
                                "stat_day" => $statDay->format('z'),
                                "stat_month" => $statDay->format('n'),
                                "stat_year" => $statDay->format('Y'),
                                "stat_week" => $statDay->format('W')
                            ])
                            ->update([
                                "completed_bookings" => DB::raw("completed_bookings+1"),
                                "on_time" => DB::raw("on_time+" . $bill->on_time),
                                "cancel_count" => DB::raw("cancel_count+" . $bill->canceled),
                                "invalid_count" => DB::raw("invalid_count+" . $bill->invalid),
                                "trouble_count" => DB::raw("trouble_count+" . $bill->trouble),
//                                "appearance_count" => DB::raw("appearance_count+" . $bill->appearance),
//                                "professionalism_count" => DB::raw("professionalism_count+" . $bill->professionalism),
//                                "cleanliness_count" => DB::raw("cleanliness_count+" . $bill->cleanliness),
//                                "quality_count" => DB::raw("quality_count+" . $bill->quality),
                                "total_income" => DB::raw("total_income+" . $bill->com_income),
                                "total_plate" => DB::raw("total_plate+" . $bill->platform_income),
                            ]);
                    } else {
                        {
                            if (is_null($bill->exe_timezone)) {
                                continue;
                            }

                            $exeTimezone = new \DateTimeZone($bill->exe_timezone);
                            $exeSettleDay = new \DateTime("@{$bill->settle_time}");
                            $exeSettleDay->setTimezone($exeTimezone);
                            BookingDayStatistic::where('company_id', $bill->exe_com_id)
                                ->where([
                                    "stat_day" => $exeSettleDay->format('z'),
                                    "stat_month" => $exeSettleDay->format('n'),
                                    "stat_year" => $exeSettleDay->format('Y'),
                                    "stat_week" => $exeSettleDay->format('W')
                                ])
                                ->update([
                                    "completed_bookings" => DB::raw("completed_bookings+1"),
                                    "on_time" => DB::raw("on_time+" . $bill->on_time),
//                                    "appearance_count" => DB::raw("appearance_count+" . $bill->appearance),
//                                    "professionalism_count" => DB::raw("professionalism_count+" . $bill->professionalism),
//                                    "cleanliness_count" => DB::raw("cleanliness_count+" . $bill->cleanliness),
//                                    "quality_count" => DB::raw("quality_count+" . $bill->quality),
                                    "total_income" => DB::raw("total_income+" . $bill->an_fee),
                                ]);
                        }


                        {
                            if (is_null($bill->own_timezone)) {
                                continue;
                            }
                            $ownTimezone = new \DateTimeZone($bill->own_timezone);
                            $ownSettleDay = new \DateTime("@{$bill->settle_time}");
                            $ownSettleDay->setTimezone($ownTimezone);
                            BookingDayStatistic::where('company_id', $bill->company_id)
                                ->where([
                                    "stat_day" => $ownSettleDay->format('z'),
                                    "stat_month" => $ownSettleDay->format('n'),
                                    "stat_year" => $ownSettleDay->format('Y'),
                                    "stat_week" => $ownSettleDay->format('W')
                                ])
                                ->update(
                                    [
                                        'completed_bookings' => DB::raw("completed_bookings+1"),               //)总单数+1
                                        'on_time' => DB::raw("on_time+" . $bill->on_time),
                                        'total_income' => DB::raw("total_income+" . $bill->com_income),    //公司收入
                                        'total_plate' => DB::raw("total_plate+" . $bill->platform_income),    //平台收入
                                        'total_an_fee' => DB::raw("total_an_fee+" . $bill->an_fee),        //an支出
                                    ]
                                );
                        }
                    }
                }
            }
        });

    }

}
