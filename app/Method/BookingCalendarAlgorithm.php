<?php
/**
 * Created by PhpStorm.
 * User: liqihai
 * Date: 2016/9/24
 * Time: 下午3:45
 */

namespace App\Method;


use App\Constants;
use App\ErrorCode;
use App\Model\Calendar;
use App\Model\CalendarEvent;
use App\Model\CalendarRecurringDay;
use App\Model\CalendarRecurringEvent;
use App\Model\Customer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BookingCalendarAlgorithm
{
    const DAY_LOOP_COUNT = 367;
    const WEEK_LOOP_COUNT = 53;
    const MONTH_LOOP_COUNT = 13;
    const YEAR_LOOP_COUNT = 2;
    private static $_instance = null;

    private function __construct()
    {
    }


    public static function getCalendar()
    {
        if (self::$_instance == null) {
            self::$_instance = new BookingCalendarAlgorithm();
        }
        return self::$_instance;
    }


    private function startTime($appointed_at)
    {
        return $appointed_at - ($appointed_at) % Constants::HALF_HOUR;
    }

    private function endTime($appointed_at, $duration)
    {
        return ceil(($appointed_at + $duration * Constants::MINUTE) / Constants::HALF_HOUR) * Constants::HALF_HOUR;
    }

    protected function matchRoutineAndAppointTime($calendar, $appointed_time, $duration, $inBooking = false)
    {
//        echo 'calendar id '.$calendar->id.'<br>';
        $routine = json_decode($calendar->routine, true);
        $startTime = $this->startTime($appointed_time);
        $endTime = $this->endTime($appointed_time, $duration);


//        echo "start of week ".($startTime)." end of week ".($endTime)."<br>";
//        echo "start of week ".date('Y-m-d H:i:s',$startTime)." end of week ".date('Y-m-d H:i:s',$endTime)."<br>";
        //增加五分钟时长
        if ($startTime < time() + 300) {
            return false;
        }
        $startDayOfWeek = date('w', $startTime);
        $endDayOfWeek = date('w', $endTime);
//        echo "start of week ".$startDayOfWeek." end of week ".$endDayOfWeek."<br>";


        $startOfDay = ($startTime % Constants::DAY_SECONDS) / Constants::HALF_HOUR;
        $endOfDay = ($endTime % Constants::DAY_SECONDS) / Constants::HALF_HOUR;

//        echo "start of day ".$startOfDay." end of day ".$endOfDay."<br>";
        if ($startDayOfWeek == $endDayOfWeek) {
            $string = substr($routine[$startDayOfWeek], $startOfDay, $endOfDay - $startOfDay);
        } else {
            $startDayString = $routine[$startDayOfWeek];
            $endDayString = $routine[$endDayOfWeek];

            $startString = substr($startDayString, $startOfDay, Constants::DAY_SECONDS - $startOfDay);
            $endString = substr($endDayString, 0, $endOfDay);

            $string = $startString . $endString;
        }
//        //检测字符串中是否包含字符1
//        echo $string."<br>";
        if (!empty(strstr($string, '1'))) {
            return false;
        }

        $events = $this->matchEvents($calendar, $startTime, $endTime, $inBooking);
//        echo $events."<br>";
//        echo   "count ".json_encode(count($events) == 0)."<br>";
        return count($events) == 0;
    }

    public function changeExistCalendarEvent(
        $booking_id,
        $driver, $car,
        $d_address, $a_address,
        $appointed_at, $duration,
        $customer_id, $company_id,
        $cost,$type)
    {
        //先删除事件
        $d_event = CalendarEvent::where([
            ['re_type', Calendar::DRIVER_TYPE],
            ['creator_id', $booking_id],
            ['creator_type', CalendarEvent::CREATOR_TYPE_BOOKING]])
            ->first();
        if (!empty($d_event)) {
            $d_event->delete();
        }
        $c_event = CalendarEvent::where([
            ['re_type', Calendar::CAR_TYPE],
            ['creator_id', $booking_id],
            ['creator_type', CalendarEvent::CREATOR_TYPE_BOOKING]])
            ->first();
        if (!empty($c_event)) {
            $c_event->delete();
        }
        $this->addDriverAndCarEventByBooking(
            $booking_id,
            $driver, $car,
            $d_address, $a_address,
            $appointed_at, $duration,
            $customer_id, $company_id,
            $cost,$type);
    }


    private function addDriverAndCarEventByBooking($booking_id,
                                                   $driver, $car,
                                                   $d_address, $a_address,
                                                   $appointed_at, $duration,
                                                   $customer_id, $company_id,
                                                   $cost, $type)
    {
        $user = Customer::where('customers.id', $customer_id)
            ->leftjoin('users', 'users.id', '=', 'customers.user_id')
            ->select('users.first_name', 'users.last_name')
            ->first();

        if ($duration < 60) {
            $time = round($duration, 1) . " minutes";
        } else {
            $time = round($duration / 60, 1) . " hours";
        }
//        if ($type == Booking::P2P) {
//            $carContent = $driver->first_name . ' take ' . $user->first_name . '· from ' . $d_address . ' to ' . $a_address;
//            $driverContent = 'take ' . $user->first_name . '·' . $user->last_name . ' from ' . $d_address . ' to ' . $a_address;
//        } elseif ($type == Booking::HOURLY) {
//            $carContent = $driver->first_name . ' take ' . $user->first_name . '· from ' . $d_address . ' for ' . $hours . 'hours';
//            $driverContent = 'take ' . $user->first_name . '·' . $user->last_name . ' on ' . $d_address . ' for ' . $hours . 'hours';
//        } elseif ($type == Booking::CUSTOM) {
//            $carContent = $driver->first_name . ' take ' . $user->first_name . '· from ' . $d_address . ' for ' . $hours . 'hours';
//            $driverContent = 'take ' . $user->first_name . '·' . $user->last_name . ' on ' . $d_address . ' for ' . $hours . 'hours';
//        } else {
//            throw new \Exception('Tommy Lee code bug');
//        }


        $carContent = "{$driver->first_name} {$driver->last_name} : Car service for {$user->first_name} {$user->last_name} which takes {$time} and $ {$cost}";
        $driverContent = "Car service for {$user->first_name} {$user->last_name} which takes {$time} and $ {$cost}";

        $startTime = $appointed_at;
        $endTime = $appointed_at + $duration * 60;

        $dst = MethodAlgorithm::checkDstForCompany($company_id,$appointed_at);

        $driverCalendar
            = Calendar::where('owner_id', $driver->driver_id)
            ->where('type', Calendar::DRIVER_TYPE)
            ->select('id',
                DB::raw("case when {$dst}=1
                then dst_routine
                else routine
                end as routine
                "),
                'type',
                'owner_id'
                )
            ->first();
//        极限容错


        $events = $this->matchEvents($driverCalendar, $startTime, $endTime, false);
        if (count($events) != 0) {
            throw new \Exception(ErrorCode::errorAddEventTimeHasBeenUsed('driver'));
        }

        $carCalendar
            = Calendar::where('owner_id', $car->car_id)
            ->where('type', Calendar::CAR_TYPE)
            ->select('id',
                DB::raw("case when {$dst}=1
                then dst_routine
                else routine
                end as routine
                "),
                'type',
                'owner_id'
                )
            ->first();
        //极限容错
        $events = $this->matchEvents($carCalendar, $startTime, $endTime, false);
        if (count($events) != 0) {
//            echo $events;
            throw new \Exception(ErrorCode::errorAddEventTimeHasBeenUsed('car'));
        }
        CalendarEvent::create([
            "re_owner_id" => $driverCalendar->owner_id,
            're_type' => $driverCalendar->type,
            "calendar_id" => $driverCalendar->id,
            "re_company_id" => $company_id,
            'content' => $driverContent,
            'start_time' => MethodAlgorithm::formatTimestampToDate($startTime),
            'end_time' => MethodAlgorithm::formatTimestampToDate($endTime),
            "creator_id" => $booking_id,
            'creator_type' => CalendarEvent::CREATOR_TYPE_BOOKING
        ],[
            "re_owner_id" => $carCalendar->owner_id,
            're_type' => $carCalendar->type,
            "calendar_id" => $carCalendar->id,
            "re_company_id" => $company_id,
            'content' => $carContent,
            'start_time' => MethodAlgorithm::formatTimestampToDate($startTime),
            'end_time' => MethodAlgorithm::formatTimestampToDate($endTime),
            "creator_id" => $booking_id,
            'creator_type' => CalendarEvent::CREATOR_TYPE_BOOKING
        ]);
    }
    private function matchEvents($calendar, $startTime, $endTime, $inBooking)
    {
        $events = CalendarEvent::where([['calendar_id', $calendar->id], ['enable', CalendarEvent::EVENT_ENABLE]])
            ->where(function ($query) use ($startTime, $endTime) {
                $query
                    ->where(function ($childQuery) use ($startTime, $endTime) {
                        $childQuery
                            ->where(DB::raw('unix_timestamp(start_time)'),'>=',$startTime)    //      |---------------|
                            ->where(DB::raw('unix_timestamp(start_time)'),'<',$endTime)      //  |------------|
                            ->where(DB::raw('unix_timestamp(end_time)'),'>',$endTime);
                    })
                    ->orWhere(function ($childQuery) use ($startTime, $endTime) {
                        $childQuery
                            ->where(DB::raw('unix_timestamp(start_time)'),'>=',$startTime)     //       |---------|
                            ->where(DB::raw('unix_timestamp(end_time)'),'<=',$endTime);        //    |---------------|
                    })
                    ->orWhere(function ($childQuery) use ($startTime, $endTime) {
                        $childQuery
                            ->where(DB::raw('unix_timestamp(start_time)'),'<=',$startTime)     //       |---------|
                            ->where(DB::raw('unix_timestamp(end_time)'),'>=',$endTime);        //         |-----|
                    })
                    ->orWhere(function ($childQuery) use ($startTime, $endTime) {
                        $childQuery
                            ->where(DB::raw('unix_timestamp(start_time)'),'<',$startTime)      //      |---------------|
                            ->where(DB::raw('unix_timestamp(end_time)'),'>',$startTime)        //               |------------|
                            ->where(DB::raw('unix_timestamp(end_time)'),'<',$endTime);
                    });
            })
            ->where(function ($query) use ($inBooking , $calendar){
                if($inBooking){
                    $query->where('id','!=',$calendar->event_id);
                }
            })
            ->get();
        return $events;
    }
}