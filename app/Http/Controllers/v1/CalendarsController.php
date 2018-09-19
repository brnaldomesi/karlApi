<?php

namespace App\Http\Controllers\v1;

use App\ErrorCode;
use App\Method\CalendarAlgorithm;
use App\Method\MethodAlgorithm;
use App\Model\Booking;
use App\Model\Calendar;
use App\Model\CalendarEvent;
use App\Model\CalendarRecurringDay;
use App\Model\CalendarRecurringEvent;
use App\Constants;
use App\Model\Customer;
use App\Model\Driver;
use \Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Input;

class CalendarsController extends Controller
{

    const daySecond = 86400;//(24*3600)
    const daySection = 48; //(0.5*3600)

    public function getDriverRoutine(Request $request)
    {
        $driver_id = $request->user->driver->id;
        $calendar = Calendar::where('calendars.owner_id', $driver_id)
            ->where('calendars.type', Calendar::DRIVER_TYPE)
            ->first();
        if (empty($calendar)) {
            return ErrorCode::errorNoObject('routine');
        }
        return ErrorCode::success($calendar->routine);
    }

    public function putDriverRoutine(Request $request)
    {
        $driver_id = $request->user->driver->id;
        $calendar = Calendar::where('calendars.owner_id', $driver_id)
            ->where('calendars.type', Calendar::DRIVER_TYPE)
            ->first();
        if (empty($calendar)) {
            return ErrorCode::errorNotExist('driver calendar');
        }
        $routine = Input::get('routine', null);
        if (is_null($routine)) {
            return ErrorCode::errorMissingParam();
        }


        $routine = explode(',', $routine);
        if (empty($routine) || !is_array($routine) || count($routine) != 7) {
            return ErrorCode::errorParam('routine');
        }
        print_r($routine);
        foreach ($routine as $rou) {
            if (!preg_match('/[0,1]{48}/', $rou)) {
//            echo $rou;
                return ErrorCode::errorParam('routine');
            }
        }
        $calendar->routine = json_encode($routine);
        $calendar->save();

        return ErrorCode::success($calendar->routine);
    }


    public function getDriverEventUpcoming(Request $request)
    {
        $driver_id = $request->user->driver->id;
        $events = CalendarEvent::where('calendar_events.re_owner_id', $driver_id)
            ->where(DB::raw('unix_timestamp(calendar_events.end_time)'), '>', time())
            ->where('calendar_events.re_type', Calendar::DRIVER_TYPE)
            ->where('calendar_events.enable', CalendarEvent::EVENT_ENABLE)
            ->leftjoin('calendar_recurring_events', 'calendar_recurring_events.id', '=', 'calendar_events.repeat_id')
            ->leftjoin('calendar_recurring_days', 'calendar_recurring_events.id', '=', 'calendar_recurring_days.repeat_event_id')
            ->select('calendar_events.id', 'calendar_events.calendar_id',
                DB::raw('unix_timestamp(calendar_events.start_time) as start_time'),
                DB::raw('unix_timestamp(calendar_events.end_time) as end_time'),
                'calendar_events.re_type as type',
                DB::raw('case when calendar_events.repeat_id is null then 0 else 1 end as `repeat`'),
                DB::raw('group_concat(calendar_recurring_days.repeat_day) as repeat_days'),
                'calendar_events.content',
                'calendar_recurring_events.repeat_type',
                'calendar_events.creator_type',
                'calendar_events.creator_id',
                'calendar_events.repeat_id')
            ->groupBy('calendar_events.id')
            ->orderBy('calendar_events.re_type', 'desc')
            ->orderBy('calendar_events.start_time', 'asc')
            ->get();
        return ErrorCode::success($events);
    }

    public function companyAddEvent(Request $request)
    {
        $admin_id = $request->user->admin->id;
        $company_id = $request->user->company_id;
        $start_time = Input::get('start_time', null);
        $end_time = Input::get('end_time', null);
        $content = Input::get('content', null);
        $owner_id = Input::get("owner_id", null);
        $type = Input::get("type", null);
        $repeat = Input::get("repeat", null);
        $repeat_type = Input::get("repeat_type", null);
        $repeat_days = Input::get("repeat_days", null);
        $time_zone = Input::get("time_zone", null);
        try {
            $event = $this->createEvent($company_id, $start_time, $end_time, $content,$repeat,
                $owner_id, $type, $admin_id, CalendarEvent::CREATOR_TYPE_ADMIN, $repeat_type, $repeat_days, $time_zone);
            return ErrorCode::success($event);
        } catch (\Exception $ex) {
            return $ex->getMessage();
        }
    }

    public function createDriverEvent(Request $request)
    {
        $driver_id = $request->user->driver->id;
        $company_id = $request->user->company_id;

        $start_time = Input::get('start_time', null);
        $end_time = Input::get('end_time', null);
        $content = Input::get('content', null);
        $repeat_type = Input::get("repeat_type", null);
        $repeat = Input::get("repeat", null);
        $repeat_days = Input::get("repeat_days", null);
        $time_zone = Input::get("time_zone", null);

        try {
            $event = $this->createEvent($company_id, $start_time, $end_time, $content,$repeat,
                $driver_id, Calendar::DRIVER_TYPE, $driver_id, CalendarEvent::CREATOR_TYPE_SELF, $repeat_type, $repeat_days, $time_zone);
            return ErrorCode::success($event);
        } catch (\Exception $ex) {
            return $ex->getMessage();
        }
    }

    private function createEvent($company_id, $start_time, $end_time, $content,$repeat,
                                 $owner_id, $type, $creator_id, $creator_type, $repeat_type, $repeat_days, $time_zone)
    {
        if (is_null($start_time) || is_null($end_time) ||
            is_null($content) || is_null($owner_id) || is_null($type)
        ) {
            throw new \Exception(ErrorCode::errorMissingParam());
        }
        if (!is_numeric($start_time) || $start_time < time()) {
            throw new \Exception(ErrorCode::errorParam('start time'));
        }
        if (!is_numeric($end_time) || $end_time <= $start_time) {
            throw new \Exception(ErrorCode::errorParam('end time'));
        }
        if (!is_numeric($type) || ($type != Calendar::DRIVER_TYPE && $type != Calendar::CAR_TYPE)) {
            throw new \Exception(ErrorCode::errorParam('type:' . $type));
        }
        $calender = Calendar::where('owner_id', $owner_id)->where('type', $type)->first();
        if (empty($calender)) {
            throw new \Exception(ErrorCode::errorNotExist('calendar'));
        }
        $state = $this->matchEvents($calender, $start_time, $end_time, false);
        if (count($state) != 0) {
            throw new \Exception(ErrorCode::errorAddEventTimeHasBeenUsed($type == 1 ? 'driver' : "car"));
        }
        if($repeat == CalendarRecurringEvent::REPEAT && !is_null($repeat_type) && ($repeat_type != '')){
            if (!is_numeric($repeat_type) || (floor($repeat_type) != $repeat_type) ||
                ($repeat_type < CalendarRecurringEvent::REPEAT_TYPE_DAY && $repeat_type > CalendarRecurringEvent::REPEAT_TYPE_YEAR)
            ) {
                throw new \Exception(ErrorCode::errorParam('repeat_type'));
            }
            try{
                new \DateTimeZone($time_zone);
            }catch(\Exception $ex){
                throw new \Exception(ErrorCode::errorParam('time_zone'));
            }
            DB::transaction(function () use (
                $company_id, $start_time, $end_time, $content, $owner_id, $type,
                $creator_id, $creator_type, $repeat_type, $repeat_days, $time_zone
            ) {

                $recurringEvent = CalendarRecurringEvent::create(
                    [
                        'owner_id' => $owner_id,
                        'owner_type' => $type,
                        'content' => $content,
                        'start_time' => MethodAlgorithm::formatTimestampToDate($start_time),
                        'duration_time' => ($end_time - $start_time),
                        'repeat_type' => $repeat_type,
                        'creator_id' => $creator_id,
                        'creator_type' => $creator_type,
                        'time_zone' => $time_zone
                    ]
                );

                if ($repeat_type == CalendarRecurringEvent::REPEAT_TYPE_WEEK) {
                    $days = explode(',', $repeat_days);
                    if (count($days) == 0) {
                        throw new \Exception(ErrorCode::errorParam('repeat_days'));
                    } else {
                        foreach ($days as $day) {
                            if (!is_numeric($day) || floor($day) != $day || $day < 1 || $day > 7) {
                                throw new \Exception(ErrorCode::errorParam('repeat_days'));
                            }
                            CalendarRecurringDay::create([
                                'repeat_event_id' => $recurringEvent->id,
                                'repeat_day' => $day
                            ]);
                        }
                    }
                }
                CalendarAlgorithm::getCalendar()->createRecurringEvent($recurringEvent->id);
            });
            return "success";
        } else {
            $event = CalendarEvent::create([
                'start_time' => MethodAlgorithm::formatTimestampToDate($start_time),
                'end_time' => MethodAlgorithm::formatTimestampToDate($end_time),
                'content' => $content,
                're_type' => $type,
                're_owner_id' => $owner_id,
                're_company_id' => $company_id,
                'calendar_id' => $calender->id,
                'creator_id' => $creator_id,
                'creator_type' => $creator_type,
            ]);
            $event = CalendarEvent::where('calendar_events.id', $event->id)
                ->leftjoin('calendar_recurring_events', 'calendar_recurring_events.id', '=', 'calendar_events.repeat_id')
                ->leftjoin('calendar_recurring_days', 'calendar_recurring_events.id', '=', 'calendar_recurring_days.repeat_event_id')
                ->select('calendar_events.id', 'calendar_events.calendar_id',
                    DB::raw('unix_timestamp(calendar_events.start_time) as start_time'),
                    DB::raw('unix_timestamp(calendar_events.end_time) as end_time'),
                    'calendar_events.re_type as type',
                    DB::raw('case when calendar_events.repeat_id is null then 0 else 1 end as `repeat`'),
                    DB::raw('group_concat(calendar_recurring_days.repeat_day) as repeat_days'),
                    'calendar_events.content',
                    'calendar_recurring_events.repeat_type',
                    'calendar_events.creator_type',
                    'calendar_events.creator_id',
                    'calendar_events.repeat_id')
                ->groupBy('calendar_events.id')
                ->first();
            return $event;
        }
    }

    public function companyDeleteEvents(Request $request, $event_id)
    {
        $company_id = $request->user->company_id;
        $repeat = Input::get('repeat', 0);
        $event = CalendarEvent::where('id', $event_id)
            ->where('re_company_id', $company_id)
            ->first();
        if (is_null($event) || empty($event)) {
            return ErrorCode::errorNotExist('event');
        }
        return $this->deleteEvent($event->re_owner_id, $event_id, $repeat, $event->re_type);
    }

    public function deleteDriverEvent(Request $request, $event_id)
    {
        $owner_id = $request->user->driver->id;
        $repeat = Input::get('repeat', 0);
        return $this->deleteEvent($owner_id, $event_id, $repeat, Calendar::DRIVER_TYPE);
    }

    private function deleteEvent($owner_id, $event_id, $repeat, $event_type)
    {
        if ($repeat == 0) {

            $event = CalendarEvent::where('id', $event_id)
                ->where('re_owner_id', $owner_id)
                ->where('re_type', $event_type)
                ->first();
            if (is_null($event) || empty($event)) {
                return ErrorCode::errorNotExist('event');
            }
            if ($event->creator_type == CalendarEvent::CREATOR_TYPE_BOOKING) {
                return ErrorCode::errorHandleDBUnauthorizedOperation();
            }

            $event->enable = CalendarEvent::EVENT_DISABLE;
            if ($event->save()) {
                return ErrorCode::success('success');
            } else {
                return ErrorCode::errorDB();
            }
        } elseif ($repeat == 1) {
            try {
                DB::transaction(function () use ($owner_id, $event_id, $repeat, $event_type) {
                    $event = CalendarEvent::where('id', $event_id)
                        ->where('re_owner_id', $owner_id)
                        ->where('re_type', $event_type)
                        ->whereNotNull('repeat_id')
                        ->first();
                    if (is_null($event) || empty($event)) {
                        throw new \Exception(ErrorCode::errorNotExist('event'));
                    }

                    if ($event->creator_type == CalendarEvent::CREATOR_TYPE_BOOKING) {
                        return ErrorCode::errorHandleDBUnauthorizedOperation();
                    }
                    //删除重复事件,
                    CalendarRecurringEvent::where('id', $event->repeat_id)->delete();
                    CalendarRecurringDay::where('repeat_event_id', $event->repeat_id)->delete();
                    CalendarEvent::where('repeat_id', $event->repeat_id)->delete();
                });
                return ErrorCode::success('success');
            } catch (\Exception $ex) {
                return $ex->getMessage();
            }
        } else {
            return ErrorCode::errorParam('repeat');
        }

    }


    public function companyGetUpcomingEvents(Request $request)
    {
        $company_id = $request->user->company_id;
        $searchType = Input::get('type', null);
        $searchId = Input::get('id', null);

        $events = DB::transaction(function () use ($company_id, $searchId, $searchType) {
            $repeats = CalendarEvent::where('calendar_events.re_company_id', $company_id)
                ->where(DB::raw('unix_timestamp(calendar_events.end_time)'), '>', time())
                ->where(function ($query) use ($searchType) {
                    if ($searchType == Calendar::CAR_TYPE || $searchType == Calendar::DRIVER_TYPE) {
                        $query->where('calendar_events.re_type', $searchType);
                    }
                })
                ->where(function ($query) use ($searchId) {
                    if (is_numeric($searchId)) {
                        $query->where('calendar_events.re_owner_id', $searchId);
                    }
                })
                ->where('calendar_events.enable', CalendarEvent::EVENT_ENABLE)
                ->whereNotNull('calendar_events.repeat_id')
                ->select('calendar_events.id', 'calendar_events.repeat_id')
                ->orderBy('repeat_id', 'asc')
                ->orderBy('start_time', 'asc')
                ->get();
            $explore = '';
            $tempRepeatId = 0;
            foreach ($repeats as $repeat) {
                if ($tempRepeatId == $repeat->repeat_id) {
                    $explore = $explore . "," . $repeat->id;
                }
                $tempRepeatId = $repeat->repeat_id;
            }
            $explore = explode(',', $explore);
            array_shift($explore);
            return CalendarEvent::where('calendar_events.re_company_id', $company_id)
                ->where(DB::raw('unix_timestamp(calendar_events.end_time)'), '>', time())
                ->where(function ($query) use ($searchType) {
                    if ($searchType == Calendar::CAR_TYPE || $searchType == Calendar::DRIVER_TYPE) {
                        $query->where('calendar_events.re_type', $searchType);
                    }
                })
                ->where(function ($query) use ($searchId) {
                    if (is_numeric($searchId)) {
                        $query->where('calendar_events.re_owner_id', $searchId);
                    }
                })
                ->where('calendar_events.enable', CalendarEvent::EVENT_ENABLE)
                ->where('calendar_events.creator_type', '!=', CalendarEvent::CREATOR_TYPE_BOOKING)
                ->leftjoin('calendar_recurring_events', 'calendar_recurring_events.id', '=', 'calendar_events.repeat_id')
                ->leftjoin('calendar_recurring_days', 'calendar_recurring_events.id', '=', 'calendar_recurring_days.repeat_event_id')
                ->where(function ($query) use ($explore){
                    if(!empty($explore) && count($explore) >0){
                        $query->whereNotIn('calendar_events.id', $explore);
                    }
                })
                ->select('calendar_events.id', 'calendar_events.calendar_id',
                    DB::raw('unix_timestamp(calendar_events.start_time) as start_time'),
                    DB::raw('unix_timestamp(calendar_events.end_time) as end_time'),
                    'calendar_events.re_type as type',
                    DB::raw('case when calendar_events.repeat_id is null then 0 else 1 end as `repeat`'),
                    'calendar_recurring_days.repeat_day',
                    'calendar_events.content',
                    'calendar_recurring_events.repeat_type',
                    'calendar_events.creator_type',
                    'calendar_events.creator_id',
                    'calendar_events.repeat_id')
                ->orderBy('calendar_events.re_type', 'desc')
                ->orderBy('calendar_events.start_time', 'asc')
                ->get();
        });
        return ErrorCode::success($events);
    }


    public function companyGetPastEvents(Request $request)
    {
        $company_id = $request->user->company_id;
        $page = Input::get('page', Constants::PAGE_DEFAULT);
        $per_page = Input::get('per_page', Constants::PER_PAGE_DEFAULT);
        if ($per_page > 100 || $per_page < 1 || !is_numeric($per_page)) {
            $per_page = Constants::PER_PAGE_DEFAULT;
        }
        if ($page < 1 || !is_numeric($page)) {
            $page = Constants::PAGE_DEFAULT;
        }
        $skip = $per_page * ($page - 1);
        $events = CalendarEvent::where('re_company_id', $company_id)
            ->where(DB::raw('unix_timestamp(calendar_events.start_time)'), '<', time())
            ->leftjoin('calendar_recurring_events', 'calendar_recurring_events.id', '=', 'calendar_events.repeat_id')
            ->select('calendar_events.id', 'calendar_events.calendar_id',
                DB::raw('unix_timestamp(calendar_events.start_time) as start_time'),
                DB::raw('unix_timestamp(calendar_events.end_time) as end_time'),
                'calendar_events.re_type as type', 'calendar_events.content',
                'calendar_events.creator_id', 'calendar_events.creator_type',
                'calendar_recurring_events.repeat_type')
            ->orderBy('calendar_events.re_type', 'desc')
            ->orderBy('calendar_events.start_time', 'asc')
            ->skip($skip)
            ->take($per_page)
            ->get();
        $count = CalendarEvent::where('re_company_id', $company_id)
            ->where(DB::raw('unix_timestamp(calendar_events.start_time)'), '<', time())
            ->leftjoin('calendar_recurring_events', 'calendar_recurring_events.id', '=', 'calendar_events.repeat_id')
            ->count();
        if (empty($events)) {
            return ErrorCode::successEmptyResult('events is empty');
        }
        $result['count'] = $count;
        $result['events'] = $events;
        return ErrorCode::success($result);
    }


    public function addDriverAndCarEventByBooking($booking_id, $driver, $car, $d_address, $a_address,
                                                  $appointed_at, $pre_time,
                                                  $duration, $customer_id,
                                                  $company_id, $type)
    {
        $user = Customer::where('customers.id', $customer_id)
            ->leftjoin('users', 'users.id', '=', 'customers.user_id')
            ->select('users.first_name', 'users.last_name')
            ->first();

        $hours = $duration / 60;
        if ($type == Booking::CHECK_TYPE_DISTANCE) {
            $carContent = $driver->first_name . ' take ' . $user->first_name . '· from ' . $d_address . ' to ' . $a_address;
            $driverContent = 'take ' . $user->first_name . '·' . $user->last_name . ' from ' . $d_address . ' to ' . $a_address;
        } elseif ($type == Booking::CHECK_TYPE_HOURLY) {
            $carContent = $driver->first_name . ' take ' . $user->first_name . '· from ' . $d_address . ' for ' . $hours . ' hours';
            $driverContent = 'take ' . $user->first_name . '·' . $user->last_name . ' on ' . $d_address . ' for ' . $hours . ' hours';
        } elseif ($type == Booking::CHECK_TYPE_CUSTOM) {
            $carContent = $driver->first_name . ' take ' . $user->first_name . '· from ' . $d_address . ' for ' . $hours . ' hours';
            $driverContent = 'take ' . $user->first_name . '·' . $user->last_name . ' on ' . $d_address . ' for ' . $hours . ' hours';
        } else {
            throw new \Exception('Tommy Lee code bug');
        }
        $startTime = $this->startTime(($appointed_at), $pre_time);
        $endTime = $this->endTime(($appointed_at), $duration);

        $driverCalendar
            = Calendar::where('owner_id', $driver->driver_id)
            ->where('type', Calendar::DRIVER_TYPE)
            ->first();
//        极限容错
        $events = $this->matchEvents($driverCalendar, $startTime, $endTime, false);
        if (count($events) != 0) {
            throw new \Exception(ErrorCode::errorAddEventTimeHasBeenUsed('driver'));
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
        ]);

        $carCalendar
            = Calendar::where('owner_id', $car->car_id)
            ->where('type', Calendar::CAR_TYPE)
            ->first();
        //极限容错
        $events = $this->matchEvents($carCalendar, $startTime, $endTime, false);
        if (count($events) != 0) {
//            echo $events;
            throw new \Exception(ErrorCode::errorAddEventTimeHasBeenUsed('car'));
        }
        CalendarEvent::create([
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


    public function checkOfferSpecifiedTimeAvailable($offer_id, $appointed_time, $pre_time, $duration)
    {
        $offerCalendar = Calendar::where('owner_id', $offer_id)
            ->where('type', Calendar::OFFER_TYPE)
            ->select("id", 'routine')->first();
        if (empty($offerCalendar)) {
            return false;
        }
        return $this->matchRoutineAndAppointTime($offerCalendar, $appointed_time, $pre_time, $duration);
    }

    public function checkCarSpecifiedTimeAvailable($car_id, $appointed_time, $pre_time, $duration)
    {
        $Calendar = Calendar::where('owner_id', $car_id)
            ->where('type', Calendar::CAR_TYPE)
            ->select("id", 'routine')->first();

        if (empty($Calendar)) {
            return false;
        }
        return $this->matchRoutineAndAppointTime($Calendar, $appointed_time, $pre_time, $duration);
    }

    public function checkDriverSpecifiedTimeAvailable($driver_id, $appointed_time, $pre_time, $duration)
    {
        $offerCalendar = Calendar::where('owner_id', $driver_id)
            ->where('type', Calendar::DRIVER_TYPE)
            ->select("id", 'routine')->first();
        if (empty($offerCalendar)) {
            return false;
        }
        return $this->matchRoutineAndAppointTime($offerCalendar, $appointed_time, $pre_time, $duration);
    }

    private function startTime($appointed_at, $pre_time)
    {
        $pre_second = $pre_time * 60;
        return $appointed_at - $pre_second - ($appointed_at - $pre_second) % (60 * 30);
    }

    private function endTime($appointed_at, $duration)
    {
        return ceil(($appointed_at + $duration * 60) / (60 * 30)) * 60 * 30;
    }


    private function matchRoutineAndAppointTime($calendar, $appointed_time, $pre_time, $duration, $inBooking = false)
    {
//        echo 'calendar id '.$calendar->id.'<br>';
        $routine = json_decode($calendar->routine, true);
        $startTime = $this->startTime($appointed_time, $pre_time);
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


        $startOfDay = ($startTime % CalendarsController::daySecond) / Constants::HALF_HOUR;
        $endOfDay = ($endTime % CalendarsController::daySecond) / Constants::HALF_HOUR;

//        echo "start of day ".$startOfDay." end of day ".$endOfDay."<br>";
        if ($startDayOfWeek == $endDayOfWeek) {
            $string = substr($routine[$startDayOfWeek], $startOfDay, $endOfDay - $startOfDay);
        } else {
            $startDayString = $routine[$startDayOfWeek];
            $endDayString = $routine[$endDayOfWeek];

            $startString = substr($startDayString, $startOfDay, CalendarsController::daySection - $startOfDay);
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

    public function checkCarSpecifiedTimeAvailableInBooking($car_id, $appointed_time, $pre_time, $duration, $booking_id)
    {
        $calendar = Calendar::
        leftjoin('calendar_events', 'calendar_events.calendar_id', '=', 'calendars.id')
            ->where('owner_id', $car_id)
            ->where('type', Calendar::CAR_TYPE)
            ->where('calendar_events.creator_id', $booking_id)
            ->where('calendar_events.creator_type', CalendarEvent::CREATOR_TYPE_BOOKING)
            ->select("calendars.id", 'calendars.routine', 'calendar_events.id as event_id')
            ->first();
        if (empty($calendar)) {
            return false;
        }
        return $this->matchRoutineAndAppointTime($calendar, $appointed_time, $pre_time, $duration, true);
    }

    public function checkDriverSpecifiedTimeAvailableInBooking($driver_id, $appointed_time, $pre_time, $duration, $booking_id)
    {
        $offerCalendar = Calendar::
        leftjoin('calendar_events', 'calendar_events.calendar_id', '=', 'calendars.id')
            ->where('calendars.owner_id', $driver_id)
            ->where('calendars.type', Calendar::DRIVER_TYPE)
            ->where('calendar_events.creator_id', $booking_id)
            ->where('calendar_events.creator_type', CalendarEvent::CREATOR_TYPE_BOOKING)
            ->select("calendars.id", 'calendars.routine', 'calendar_events.id as event_id')
            ->first();
        if (empty($offerCalendar)) {
//            echo 'empty drevier here';
            return false;
        }
        return $this->matchRoutineAndAppointTime($offerCalendar, $appointed_time, $pre_time, $duration, true);
    }

    public function changeExistCalendarEvent($booking_id, $driver, $car, $d_address, $a_address, $appointed_at,
                                             $pre_time, $duration, $customer_id, $company_id,
                                             $type)
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
        $this->addDriverAndCarEventByBooking($booking_id, $driver, $car, $d_address, $a_address, $appointed_at,
            $pre_time, $duration, $customer_id, $company_id,
            $type);
    }

    private function matchEvents($calendar, $startTime, $endTime, $inBooking)
    {
        $raw = CalendarEvent::checkEventTime($startTime,$endTime);
        if ($inBooking) {
            $raw = " `id` != '" . $calendar->event_id . "' AND" . $raw;
        }
        $events = CalendarEvent::where([['calendar_id', $calendar->id], ['enable', CalendarEvent::EVENT_ENABLE]])
            ->whereRaw($raw)
            ->get();
        return $events;
    }


    public function companiesGetEvents(Request $request)
    {
        $company_id = $request->user->company_id;
        $searchType = Input::get('type', null);
        $searchId = Input::get('id', null);
        $per_page = Input::get('per_page', 100);
        $page = Input::get('page', 1);
        $start_time = Input::get('start_time', null);
        $end_time = Input::get('end_time', null);
        $orderBy = Input::get('order_by', 0);
        if ($per_page > 100 || $per_page < 1 || !is_numeric($per_page)) {
            $per_page = Constants::PER_PAGE_DEFAULT;
        }
        if ($page < 1 || !is_numeric($page)) {
            $page = Constants::PAGE_DEFAULT;
        }
        if (is_null($start_time) || !is_numeric($start_time)) {
            return ErrorCode::errorParam('start time');
        }

        if (is_null($end_time) || !is_numeric($end_time)) {
            return ErrorCode::errorParam('end time');
        }
        if (!is_numeric($searchType) || ($searchType != Calendar::DRIVER_TYPE && $searchType != Calendar::CAR_TYPE)) {
            $searchType = null;
        }

        if (!is_numeric($searchId)) {
            $searchId = null;
        }

        if (!is_numeric($orderBy) || ($orderBy != Constants::ORDER_BY_ASC && $orderBy != Constants::ORDER_BY_DESC)) {
            $orderBy = "ASC";
        } else {
            $orderBy = $orderBy == Constants::ORDER_BY_ASC ? "ASC" : "DESC";
        }

        $skip = $per_page * ($page - 1);

        $events = CalendarEvent::where('calendar_events.re_company_id', $company_id)
            ->where(function ($query) use ($searchId, $searchType) {
                $query->where('calendar_events.enable', CalendarEvent::EVENT_ENABLE);
                if (!is_null($searchType)) {
                    $query->where('calendar_events.re_type', $searchType);
                }
                if (!is_null($searchId)) {
                    $query->where('calendar_events.re_owner_id', $searchId);
                }
            })
            ->whereBetween(DB::raw('unix_timestamp(calendar_events.end_time)'), [$start_time, $end_time])
            ->leftjoin('calendar_recurring_events', 'calendar_recurring_events.id', '=', 'calendar_events.repeat_id')
            ->leftjoin('calendar_recurring_days', 'calendar_recurring_events.id', '=', 'calendar_recurring_days.repeat_event_id')
            ->select('calendar_events.id', 'calendar_events.calendar_id',
                DB::raw('unix_timestamp(calendar_events.start_time) as start_time'),
                DB::raw('unix_timestamp(calendar_events.end_time) as end_time'),
                'calendar_events.re_type as type',
                DB::raw('case when calendar_events.repeat_id is null then 0 else 1 end as `repeat`'),
                DB::raw('group_concat(calendar_recurring_days.repeat_day) as repeat_days'),
                'calendar_events.content',
                'calendar_recurring_events.repeat_type',
                'calendar_events.creator_type',
                'calendar_events.creator_id',
                'calendar_events.repeat_id')
            ->groupBy('calendar_events.id')
            ->orderBy('calendar_events.start_time', $orderBy)
            ->skip($skip)
            ->take($per_page)
            ->get();
        $eventCount = CalendarEvent::where('calendar_events.re_company_id', $company_id)
            ->where(function ($query) use ($searchId, $searchType) {
                $query->where('calendar_events.enable', CalendarEvent::EVENT_ENABLE);
                if (!is_null($searchType)) {
                    $query->where('calendar_events.re_type', $searchType);
                }
                if (!is_null($searchId)) {
                    $query->where('calendar_events.re_owner_id', $searchId);
                }
            })
            ->whereBetween(DB::raw('unix_timestamp(calendar_events.start_time)'), [$start_time, $end_time])
            ->leftjoin('calendar_recurring_events', 'calendar_recurring_events.id', '=', 'calendar_events.repeat_id')
            ->leftjoin('calendar_recurring_days', 'calendar_recurring_events.id', '=', 'calendar_recurring_days.repeat_event_id')
            ->count();

        if ($eventCount == 0) {
            return ErrorCode::successEmptyResult('no events');
        }
        return ErrorCode::success(['total' => $eventCount, 'events' => $events]);
    }

    public function driversGetEvents(Request $request)
    {
        $driver_id = $request->user->driver->id;
        $start_time = Input::get('start_time', null);
        $end_time = Input::get('end_time', null);
        $per_page = Input::get('per_page', 100);
        $page = Input::get('page', 1);
        $orderBy = Input::get('order_by', 0);
        if (!is_numeric($orderBy) || ($orderBy != Constants::ORDER_BY_ASC && $orderBy != Constants::ORDER_BY_DESC)) {
            $orderBy = "ASC";
        } else {
            $orderBy = $orderBy == Constants::ORDER_BY_ASC ? "ASC" : "DESC";

        }
        if (is_null($start_time) || !is_numeric($start_time)) {
            return ErrorCode::errorParam('start time');
        }

        if (is_null($end_time) || !is_numeric($end_time)) {
            return ErrorCode::errorParam('end time');
        }

        if ($per_page > 100 || $per_page < 1 || !is_numeric($per_page)) {
            $per_page = Constants::PER_PAGE_DEFAULT;
        }
        if ($page < 1 || !is_numeric($page)) {
            $page = Constants::PAGE_DEFAULT;
        }
        $skip = $per_page * ($page - 1);

        $events = CalendarEvent::where('calendar_events.re_owner_id', $driver_id)
            ->whereBetween(DB::raw('unix_timestamp(calendar_events.end_time)'), [$start_time, $end_time])
            ->where('calendar_events.re_type', Calendar::DRIVER_TYPE)
            ->where('calendar_events.enable', CalendarEvent::EVENT_ENABLE)
            ->leftjoin('calendar_recurring_events', 'calendar_recurring_events.id', '=', 'calendar_events.repeat_id')
            ->leftjoin('calendar_recurring_days', 'calendar_recurring_events.id', '=', 'calendar_recurring_days.repeat_event_id')
            ->select('calendar_events.id', 'calendar_events.calendar_id',
                DB::raw('unix_timestamp(calendar_events.start_time) as start_time'),
                DB::raw('unix_timestamp(calendar_events.end_time) as end_time'),
                'calendar_events.re_type as type',
                DB::raw('case when calendar_events.repeat_id is null then 0 else 1 end as `repeat`'),
                DB::raw('group_concat(calendar_recurring_days.repeat_day) as repeat_days'),
                'calendar_events.content',
                'calendar_recurring_events.repeat_type',
                'calendar_events.creator_type',
                'calendar_events.creator_id',
                'calendar_events.repeat_id')
            ->groupBy('calendar_events.id')
            ->orderBy('calendar_events.start_time', $orderBy)
            ->skip($skip)
            ->take($per_page)
            ->get();
        $eventsCount = CalendarEvent::where('calendar_events.re_owner_id', $driver_id)
            ->whereBetween(DB::raw('unix_timestamp(calendar_events.end_time)'), [$start_time, $end_time])
            ->where('calendar_events.re_type', Calendar::DRIVER_TYPE)
            ->where('calendar_events.enable', CalendarEvent::EVENT_ENABLE)
            ->count();
        if ($eventsCount <= 0) {
            return ErrorCode::successEmptyResult('empty event');
        }
        return ErrorCode::success(['total' => $eventsCount, 'events' => $events]);
    }

    public function companiesGetEventsCounts(Request $request)
    {
        $company_id = $request->user->company_id;
        $searchType = Input::get('type', null);
        $searchId = Input::get('id', null);
        $start_time = Input::get('start_time', null);
        $end_time = Input::get('end_time', null);
        if (is_null($start_time) || !is_numeric($start_time)) {
            return ErrorCode::errorParam('start time');
        }

        if (is_null($end_time) || !is_numeric($end_time)) {
            return ErrorCode::errorParam('end time');
        }
        if (!is_numeric($searchType) || ($searchType != Calendar::DRIVER_TYPE && $searchType != Calendar::CAR_TYPE)) {
            $searchType = null;
        }

        if (!is_numeric($searchId)) {
            $searchId = null;
        }
        $count = floor(($end_time - $start_time) / (24 * 3600));
        if ($count < 1) {
            return ErrorCode::errorParam('start time and end time');
        }
        $sql = '';
        for ($i = 0; $i < $count; $i++) {
            $start = $start_time + $i * 24 * 3600;
            $end = $start + 24 * 3600;
            $sql = $sql . "sum(case when unix_timestamp(calendar_events.start_time)>=" . $start . " AND unix_timestamp(calendar_events.start_time)<" . $end . " THEN
  1
  ELSE 0 end )as '" . $start . "~" . $end . "',";
        }
        if (empty($sql)) {
            return ErrorCode::errorParam('start time and end time');
        }
        $sql = substr($sql, 0, -1);
        $result = CalendarEvent::where('calendar_events.re_company_id', $company_id)
            ->where(function ($query) use ($searchId, $searchType) {
                $query->where('calendar_events.enable', CalendarEvent::EVENT_ENABLE);
                if (!is_null($searchType)) {
                    $query->where('calendar_events.re_type', $searchType);
                }
                if (!is_null($searchId)) {
                    $query->where('calendar_events.re_owner_id', $searchId);
                }
            })
            ->whereBetween(DB::raw('unix_timestamp(calendar_events.start_time)'), [$start_time, $end_time])
            ->leftjoin('calendar_recurring_events', 'calendar_recurring_events.id', '=', 'calendar_events.repeat_id')
            ->leftjoin('calendar_recurring_days', 'calendar_recurring_events.id', '=', 'calendar_recurring_days.repeat_event_id')
            ->select(DB::raw($sql))
            ->first();

        $items = $result->toArray();
        $temp = array();
        foreach (array_keys($items) as $item) {
            $time = explode("~", $item);
            array_push($temp, ["start_time" => $time[0], "end_time" => $time[1], "counts" => $items[$item]]);
        }
        return ErrorCode::success($temp);
    }


    public function driversGetEventCounts(Request $request)
    {
        $driver_id = $request->user->driver->id;
        $start_time = Input::get('start_time', null);
        $end_time = Input::get('end_time', null);
        if (is_null($start_time) || !is_numeric($start_time)) {
            return ErrorCode::errorParam('start time');
        }

        if (is_null($end_time) || !is_numeric($end_time)) {
            return ErrorCode::errorParam('end time');
        }
        $count = floor(($end_time - $start_time) / (24 * 3600));
        if ($count < 1) {
            return ErrorCode::errorParam('start time and end time');
        }
        $sql = '';
        for ($i = 0; $i < $count; $i++) {
            $start = $start_time + $i * 24 * 3600;
            $end = $start + 24 * 3600;
            $sql = $sql . "sum(case when unix_timestamp(calendar_events.start_time)>=" . $start . " AND unix_timestamp(calendar_events.start_time)<" . $end . " THEN
  1
  ELSE 0 end )as '" . $start . "~" . $end . "',";
        }
        if (empty($sql)) {
            return ErrorCode::errorParam('start time and end time');
        }
        $sql = substr($sql, 0, -1);
        $result = CalendarEvent::where('calendar_events.re_owner_id', $driver_id)
            ->whereBetween(DB::raw('unix_timestamp(calendar_events.start_time)'), [$start_time, $end_time])
            ->where('calendar_events.re_type', Calendar::DRIVER_TYPE)
            ->where('calendar_events.enable', CalendarEvent::EVENT_ENABLE)
            ->select(DB::raw($sql))
            ->first();
        $items = $result->toArray();
        $temp = array();
        foreach (array_keys($items) as $item) {
            $time = explode("~", $item);
            array_push($temp, ["start_time" => $time[0], "end_time" => $time[1], "counts" => $items[$item]]);
        }
        return ErrorCode::success($temp);
    }
}