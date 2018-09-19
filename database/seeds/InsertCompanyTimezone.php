<?php
use Illuminate\Support\Facades\DB;

/**
 * Created by PhpStorm.
 * User: liqihai
 * Date: 16/9/19
 * Time: 下午3:31
 */

class InsertCompanyTimezone extends BaseSeeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    protected function task()
    {
        $companies = \App\Model\Company::all();
        foreach ($companies as $company) {
            $timezone = \App\Method\GeoLocationAlgorithm::getInstance()
                ->getLocationTime($company->lat,$company->lng,time());
            $company->timezone= $timezone->timeZoneId;
            $company->save();

        }
    }
}
