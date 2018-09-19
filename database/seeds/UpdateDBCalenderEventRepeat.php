<?php
use Illuminate\Support\Facades\DB;

/**
 * Created by PhpStorm.
 * User: liqihai
 * Date: 16/9/19
 * Time: 下午3:31
 */
class UpdateDBCalenderEventRepeat extends BaseSeeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */


    protected function task()
    {
        DB::transaction(function () {
            $recurringEvents = \App\Model\CalendarRecurringEvent::all();
            foreach ($recurringEvents as $recurringEvent) {
                if (is_numeric($recurringEvent->time_zone) && $recurringEvent->time_zone >=0) {
                    $recurringEvent->delete();
                    \App\Model\CalendarRecurringDay::where('repeat_event_id',$recurringEvent->id)->delete();
                    \App\Model\CalendarEvent::where("repeat_id", $recurringEvent->id)->delete();
                }
                if (is_numeric($recurringEvent->time_zone) && $recurringEvent->time_zone == -4) {
                    $recurringEvent->time_zone = "America/New_York";
                    $recurringEvent->save();
                    \App\Model\CalendarEvent::where("repeat_id", $recurringEvent->id)->delete();
                }
                if (is_numeric($recurringEvent->time_zone) && $recurringEvent->time_zone == -5) {
                    $recurringEvent->time_zone = "America/Chicago";
                    $recurringEvent->save();
                    \App\Model\CalendarEvent::where("repeat_id", $recurringEvent->id)->delete();
                }
                if (is_numeric($recurringEvent->time_zone) && $recurringEvent->time_zone == -6) {
                    $recurringEvent->time_zone = "America/Denver";
                    $recurringEvent->save();
                    \App\Model\CalendarEvent::where("repeat_id", $recurringEvent->id)->delete();
                }
                if (is_numeric($recurringEvent->time_zone) && $recurringEvent->time_zone == -7) {
                    $recurringEvent->time_zone = "America/Los_Angeles";
                    $recurringEvent->save();
                    \App\Model\CalendarEvent::where("repeat_id", $recurringEvent->id)->delete();
                }
                if (is_numeric($recurringEvent->time_zone) && $recurringEvent->time_zone == -8) {
                    $recurringEvent->time_zone = "America/Anchorage";
                    $recurringEvent->save();
                    \App\Model\CalendarEvent::where("repeat_id", $recurringEvent->id)->delete();
                }
                if (is_numeric($recurringEvent->time_zone) && $recurringEvent->time_zone == -9) {
                    $recurringEvent->time_zone = "America/Adak";
                    $recurringEvent->save();
                    \App\Model\CalendarEvent::where("repeat_id", $recurringEvent->id)->delete();
                }
                if (is_numeric($recurringEvent->time_zone) && $recurringEvent->time_zone == -9) {
                    $recurringEvent->time_zone = "America/Honolulu";
                    $recurringEvent->save();
                    \App\Model\CalendarEvent::where("repeat_id", $recurringEvent->id)->delete();
                }

            }
            \App\Method\CalendarAlgorithm::getCalendar()->loopAddRecurringEvents();
        });
    }

}
