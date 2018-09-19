<?php
use Illuminate\Support\Facades\DB;
use App\Model\Company;
use App\Model\Calendar;

/**
 * Created by PhpStorm.
 * User: wangjun
 * Date: 16/12/28
 * Time: 下午6:19
 */
class UpdateCompaniesDst extends BaseSeeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    protected function task()
    {
        DB::transaction(function () {
            $companies = Company::select('id','timezone')->get();

            foreach ($companies as $company) {

                $date = new DateTime('2016-07-01', new DateTimeZone($company->timezone));
                $dst = $date->format('I');

                if ($dst == 1) {
                    $company->dst = 1;
                    $company->save();
                } else {
                    $company->dst = 0;
                    $company->save();
                }
            }

            DB::update("UPDATE calendars
                    LEFT JOIN companies ON calendars.company_id = companies.id
                    SET calendars.dst = ifnull(companies.dst,0);");

            $calendars = Calendar::where('dst', 1)->get();
            if (empty($calendars) || $calendars->count() == 0) {
                echo "calendars is empty";
            }

            foreach ($calendars as $calendar) {
                $routine = json_decode($calendar->routine, true);
                if (empty($routine) || !is_array($routine)) {
                    echo 'calender error';
                } else {
                    if (sizeof($routine) != 7) {
                        echo 'calender error';
                    } else {
                        foreach ($routine as $cal) {
                            if (!preg_match("/[0,1]{48}/", $cal)) {
                                echo 'calender error';
                            }
                        }
                    }
                }
                $finalStr = App\Method\MethodAlgorithm::shiftString($routine);
                $calendar->dst_routine = $finalStr;
                $calendar->save();
            }
        });
    }
}
