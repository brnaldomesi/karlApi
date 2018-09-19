<?php

/**
 * Created by PhpStorm.
 * User: liqihai
 * Date: 16/9/19
 * Time: 下午3:31
 */

class InsertCompanyCalendarTimezone extends BaseSeeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    protected function task()
    {
        $companies = \App\Model\Company::select("timezone","id")->get();
        foreach ($companies as $company) {
            $date = new \DateTime();
            $date->setTimezone(new \DateTimeZone($company->timezone));
            $time = $date->format("Z")/3600;
            \App\Model\Calendar::where("company_id",$company->id)->update(["timezone"=>$time]);
        }
    }
}
