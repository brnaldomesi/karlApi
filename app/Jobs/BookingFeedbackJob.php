<?php

namespace App\Jobs;

use App\Model\Bill;
use App\Model\BookingDayStatistic;
use App\Model\Feedback;
use App\Model\Offer;
use App\QueueName;

/**
 * MARK STAT
 * Class BookingStatisticJob
 * @package App\Jobs
 */
class BookingFeedbackJob extends Job
{

    private $orderId;

    /**
     * BookingStatisticJob constructor.
     * @param $orderId
     */
    public function __construct($orderId)
    {
        $this->orderId = $orderId;
        $this->onQueue(QueueName::BookingFeedback);
    }


    /**
     * Execute the job.
     *
     * @return void
     */
    public function work()
    {
        //
        $feedback = Feedback::leftjoin('orders', 'orders.id', '=', 'feedbacks.order_id')
            ->leftjoin("bookings", "bills.booking_id", "=", "bookings.id")
            ->leftjoin("companies as exe_com", "bookings.exe_com_id", "=", "exe_com.id")
            ->where('feedbacks.order_id', $this->orderId)
            ->select(
                \DB::raw("unix_timestamp(bookings.appointed_at) as settle_time"),
                "bookings.exe_com_id",
                "exe_com.timezone as exe_timezone",
                "feedbacks.appearance",
                "feedbacks.professionalism",
                "feedbacks.driving_ability",
                "feedbacks.cleanliness",
                "feedbacks.quality"
            )
            ->first();
        $timeZone = new \DateTimeZone($feedback->exe_timezone);
        $settleDay = new \DateTime("@{$feedback->settle_time}");
        $settleDay->setTimezone($timeZone);
        BookingDayStatistic::where('company_id', $feedback->exe_com_id)
            ->where([
                "stat_day" => $settleDay->format('z'),
                "stat_month" => $settleDay->format('n'),
                "stat_year" => $settleDay->format('Y'),
                "stat_week" => $settleDay->format('W')
            ])
            ->update([
                    'appearance_count' => "appearance_count+" . $feedback->appearance,
                    'professionalism_count' => "professionalism_count+" . $feedback->professionalism,
                    'driving_count' => "driving_count+" . $feedback->driving_ability,
                    'cleanliness_count' => "cleanliness_count+" . $feedback->cleanliness,
                    'quality_count' => "quality_count+" . $feedback->quality
                ]
            );

    }
}
