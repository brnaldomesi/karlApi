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
use App\Model\CalendarEvent;
use App\Model\CalendarRecurringDay;
use App\Model\CalendarRecurringEvent;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CalendarAlgorithm
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
            self::$_instance = new CalendarAlgorithm();
        }
        return self::$_instance;
    }

    public function createRecurringEvent($recurringEventId)
    {
        $calendar = $this->getCalendars($recurringEventId)->first();

        $startTime = $calendar->start_time;
        if ($calendar->repeat_type == CalendarRecurringEvent::REPEAT_TYPE_DAY) {
            $this->loopAddDayRepeatEvent(self::DAY_LOOP_COUNT, $startTime, $calendar);
        } elseif ($calendar->repeat_type == CalendarRecurringEvent::REPEAT_TYPE_WEEK) {
            $this->loopAddWeekRepeatEvent(self::WEEK_LOOP_COUNT, $startTime, $calendar);
        } elseif ($calendar->repeat_type == CalendarRecurringEvent::REPEAT_TYPE_MONTH) {
            $this->loopAddMonthRepeatEvent(self::MONTH_LOOP_COUNT, $startTime, $calendar);
        } elseif ($calendar->repeat_type == CalendarRecurringEvent::REPEAT_TYPE_YEAR) {
            $this->loopAddYearRepeatEvent(self::YEAR_LOOP_COUNT, $startTime, $calendar);
        }

    }


    public function loopAddRecurringEvents()
    {
        $calendars = $this->getCalendars()->get();

        foreach ($calendars as $calendar) {
            $eventCount = CalendarEvent::where('repeat_id', $calendar->repeat_id)->count();
            $maxTime = CalendarEvent::where('repeat_id', $calendar->repeat_id)
                ->max(DB::raw("unix_timestamp(start_time)"));
            if(is_null($maxTime)){
                $maxTime = $calendar->start_time;
            }
            $startTime = new \DateTime("@{$maxTime}");
            $startTime->setTimezone(new \DateTimeZone($calendar->time_zone));
            if ($calendar->repeat_type == CalendarRecurringEvent::REPEAT_TYPE_DAY) {
                $loopCount = self::DAY_LOOP_COUNT - $eventCount;
                if ($loopCount > 0) {
                    date_add($startTime, date_interval_create_from_date_string("1 day"));
                    $this->loopAddDayRepeatEvent($loopCount, strtotime($startTime->format('Y-m-d H:i:s O')), $calendar);
                }
            } elseif ($calendar->repeat_type == CalendarRecurringEvent::REPEAT_TYPE_WEEK) {
                $loopCount = self::WEEK_LOOP_COUNT - $eventCount;
                if ($loopCount > 0) {
                    date_add($startTime, date_interval_create_from_date_string("1 week"));
                    $this->loopAddWeekRepeatEvent($loopCount, strtotime($startTime->format('Y-m-d H:i:s O')), $calendar);
                }
            } elseif ($calendar->repeat_type == CalendarRecurringEvent::REPEAT_TYPE_MONTH) {
                $loopCount = self::MONTH_LOOP_COUNT - $eventCount;
                if ($loopCount > 0) {
                    date_add($startTime, date_interval_create_from_date_string("1 month"));
                    $this->loopAddMonthRepeatEvent($loopCount, strtotime($startTime->format('Y-m-d H:i:s O')), $calendar);
                }
            } elseif ($calendar->repeat_type == CalendarRecurringEvent::REPEAT_TYPE_YEAR) {
                $loopCount = self::YEAR_LOOP_COUNT - $eventCount;
                if ($loopCount > 0) {
                    date_add($startTime, date_interval_create_from_date_string("1 year"));
                    $this->loopAddYearRepeatEvent($loopCount, strtotime($startTime->format('Y-m-d H:i:s O')), $calendar);
                }
            }
        }
    }

    /**
     * @param null $calenderId
     * @return mixed
     */
    private function getCalendars($calenderId = null)
    {
        return CalendarRecurringEvent::leftjoin('calendars', DB::raw(''), DB::raw(''),
            DB::raw('calendars.owner_id=calendar_recurring_events.owner_id and 
                calendars.type=calendar_recurring_events.owner_type'))
            ->where(function ($query) use ($calenderId) {
                if (!empty($calenderId)) {
                    $query->where('calendar_recurring_events.id', $calenderId);
                }
            })
            ->select('calendar_recurring_events.id as repeat_id',
                'calendar_recurring_events.owner_id',
                'calendar_recurring_events.owner_type',
                'calendar_recurring_events.creator_id',
                'calendar_recurring_events.creator_type',
                DB::raw('unix_timestamp(calendar_recurring_events.start_time) as start_time'),
                'calendar_recurring_events.duration_time',
                'calendar_recurring_events.content',
                'calendar_recurring_events.repeat_type',
                'calendar_recurring_events.time_zone',
                'calendars.id AS calendar_id',
                'calendars.company_id AS company_id');
    }

    private function loopAddDayRepeatEvent($loopTime, $startTime, $calendar)
    {
        for ($i = 0; $i < $loopTime; $i++) {
            if ($i > 0) {
                $time = new \DateTime("@{$startTime}");
                $time->setTimezone(new \DateTimeZone($calendar->time_zone));
                $tempS = strtotime($time->format('Y-m-d H:i:s O'));
                date_add($time, date_interval_create_from_date_string("1 day"));
                $startTime = strtotime($time->format('Y-m-d H:i:s O'));
                if(($startTime-$tempS)/3600 != 24){
                    Log::info("time is ".$time->format('Y-m-d H:i:s O')." timestamp".$startTime);
                }
            }
            $endTime = $startTime + $calendar->duration_time;
            $eventCount = CalendarEvent::where("enable", CalendarEvent::EVENT_ENABLE)
                ->where('re_type', $calendar->owner_type)
                ->where('re_owner_id', $calendar->owner_id)
                ->whereRaw(CalendarEvent::checkEventTime($startTime, $endTime))
                ->count();
            if ($eventCount > 0) {
                throw new \Exception(ErrorCode::errorAddEventTimeHasBeenUsed($calendar->repeat_id));
            } else {
                CalendarEvent::create(
                    [
                        "calendar_id" => $calendar->calendar_id,
                        "start_time" => MethodAlgorithm::formatTimestampToDate($startTime),
                        "end_time" => MethodAlgorithm::formatTimestampToDate($endTime),
                        "content" => $calendar->content,
                        "re_type" => $calendar->owner_type,
                        "re_owner_id" => $calendar->owner_id,
                        "re_company_id" => $calendar->company_id,
                        "creator_id" => $calendar->creator_id,
                        "creator_type" => $calendar->creator_type,
                        "enable" => CalendarEvent::EVENT_ENABLE,
                        "repeat_id" => $calendar->repeat_id,
                    ]
                );
            }
        }
    }

    private function loopAddWeekRepeatEvent($loopTime, $startTime, $calendar)
    {
        $repeatDays = CalendarRecurringDay::where('repeat_event_id', $calendar->repeat_id)
            ->select('repeat_day')
            ->get();
        foreach ($repeatDays as $repeatDay) {
            $tempStartTime = $startTime;
            $tempTime = new \DateTime("@{$tempStartTime}");
            $tempTime->setTimezone(new \DateTimeZone($calendar->time_zone));
            $dayOfWeek = $tempTime->format("w")+1;
            if ($dayOfWeek <= $repeatDay->repeat_day) {
                date_add($tempTime, date_interval_create_from_date_string(($repeatDay->repeat_day - $dayOfWeek)." day"));
            } else {
                date_add($tempTime, date_interval_create_from_date_string((7 - ($dayOfWeek - $repeatDay->repeat_day))." day"));
            }

            for ($i = 0; $i < $loopTime; $i++) {
                if ($i > 0) {
                    date_add($tempTime, date_interval_create_from_date_string("1 week"));
                }
                $tempStartTime = strtotime($tempTime->format('Y-m-d H:i:s O'));
                $endTime = $tempStartTime + $calendar->duration_time;
                $eventCount = CalendarEvent::where("enable", CalendarEvent::EVENT_ENABLE)
                    ->where('re_type', $calendar->owner_type)
                    ->where('re_owner_id', $calendar->owner_id)
                    ->whereRaw(CalendarEvent::checkEventTime($tempStartTime, $endTime))
                    ->count();
                if ($eventCount > 0) {
                    throw new \Exception(ErrorCode::errorAddEventTimeHasBeenUsed($calendar->repeat_id));
                } else {
                    CalendarEvent::create(
                        [
                            "calendar_id" => $calendar->calendar_id,
                            "start_time" => MethodAlgorithm::formatTimestampToDate($tempStartTime),
                            "end_time" => MethodAlgorithm::formatTimestampToDate($endTime),
                            "content" => $calendar->content,
                            "re_type" => $calendar->owner_type,
                            "re_owner_id" => $calendar->owner_id,
                            "re_company_id" => $calendar->company_id,
                            "creator_id" => $calendar->creator_id,
                            "creator_type" => $calendar->creator_type,
                            "enable" => CalendarEvent::EVENT_ENABLE,
                            "repeat_id" => $calendar->repeat_id,
                        ]
                    );
                }
            }
        }
    }


    private function loopAddMonthRepeatEvent($loopTime, $startTime, $calendar)
    {
        for ($i = 0; $i < $loopTime; $i++) {
            $tempStartTime = $startTime;
            $startTimeDate = new \DateTime("@{$tempStartTime}");
            $startTimeDate->setTimezone(new \DateTimeZone($calendar->time_zone));
            date_add($startTimeDate, date_interval_create_from_date_string("{$i} month"));
            $startTimeDate = $startTimeDate->format('Y-m-d H:i:s O');
            $tempStartTime = strtotime($startTimeDate);
            $dayOfStart = MethodAlgorithm::formatTimestampToDate($startTime, 'j');
            $dayOfTemp = MethodAlgorithm::formatTimestampToDate($tempStartTime, 'j');
            if ($dayOfStart != $dayOfTemp) {
                continue;
            }
            $endTime = $tempStartTime + $calendar->duration_time;
            $eventCount = CalendarEvent::where("enable", CalendarEvent::EVENT_ENABLE)
                ->where('re_type', $calendar->owner_type)
                ->where('re_owner_id', $calendar->owner_id)
                ->whereRaw(CalendarEvent::checkEventTime($tempStartTime, $endTime))
                ->count();
            if ($eventCount > 0) {
                throw new \Exception(ErrorCode::errorAddEventTimeHasBeenUsed($calendar->repeat_id));
            } else {
                CalendarEvent::create(
                    [
                        "calendar_id" => $calendar->calendar_id,
                        "start_time" => MethodAlgorithm::formatTimestampToDate($tempStartTime),
                        "end_time" => MethodAlgorithm::formatTimestampToDate($endTime),
                        "content" => $calendar->content,
                        "re_type" => $calendar->owner_type,
                        "re_owner_id" => $calendar->owner_id,
                        "re_company_id" => $calendar->company_id,
                        "creator_id" => $calendar->creator_id,
                        "creator_type" => $calendar->creator_type,
                        "enable" => CalendarEvent::EVENT_ENABLE,
                        "repeat_id" => $calendar->repeat_id,
                    ]
                );
            }
        }
    }

    private function loopAddYearRepeatEvent($loopTime, $startTime, $calendar)
    {
        for ($i = 0; $i < $loopTime; $i++) {
            $tempStartTime = $startTime;
            $startTimeDate = new \DateTime("@{$tempStartTime}");
            $startTimeDate->setTimezone(new \DateTimeZone($calendar->time_zone));
            date_add($startTimeDate, date_interval_create_from_date_string("{$i} year"));
            $startTimeDate = $startTimeDate->format('Y-m-d H:i:s O');
            $tempStartTime = strtotime($startTimeDate);
            $monthOfStart = MethodAlgorithm::formatTimestampToDate($startTime, 'n');
            $dayOfStart = MethodAlgorithm::formatTimestampToDate($startTime, 'j');
            $monthOfTemp = MethodAlgorithm::formatTimestampToDate($tempStartTime, 'n');
            $dayOfTemp = MethodAlgorithm::formatTimestampToDate($tempStartTime, 'j');
            if ($monthOfStart != $monthOfTemp || $dayOfStart != $dayOfTemp) {
                continue;
            }

            $endTime = $tempStartTime + $calendar->duration_time;
            $eventCount = CalendarEvent::where("enable", CalendarEvent::EVENT_ENABLE)
                ->where('re_type', $calendar->owner_type)
                ->where('re_owner_id', $calendar->owner_id)
                ->whereRaw(CalendarEvent::checkEventTime($tempStartTime, $endTime))
                ->get();
            if (count($eventCount) > 0) {
                throw new \Exception(ErrorCode::errorAddEventTimeHasBeenUsed($calendar->repeat_id));
            } else {
                CalendarEvent::create(
                    [
                        "calendar_id" => $calendar->calendar_id,
                        "start_time" => MethodAlgorithm::formatTimestampToDate($tempStartTime),
                        "end_time" => MethodAlgorithm::formatTimestampToDate($endTime),
                        "content" => $calendar->content,
                        "re_type" => $calendar->owner_type,
                        "re_owner_id" => $calendar->owner_id,
                        "re_company_id" => $calendar->company_id,
                        "creator_id" => $calendar->creator_id,
                        "creator_type" => $calendar->creator_type,
                        "enable" => CalendarEvent::EVENT_ENABLE,
                        "repeat_id" => $calendar->repeat_id,
                    ]
                );
            }
        }
    }

}