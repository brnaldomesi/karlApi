<?php

namespace App\Jobs;

use App\Constants;
use App\QueueName;
use DB;

class C2CMatchJob extends Job
{
    private $comId;

    public function __construct($comId)
    {
        //
        $this->comId = $comId;
        $this->onQueue(QueueName::Com2ComMatch);
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function work()
    {
        //
        DB::transaction(function () {
            $company = \App\Model\Company::leftjoin('company_an_settings', "company_an_settings.company_id", "=", "companies.id")
                ->leftjoin('company_settings', "company_settings.company_id", "=", "companies.id")
                ->select(
                    "companies.id",
                    "lat",
                    "lng",
                    DB::raw("if(company_an_settings.unit=" . Constants::UNIT_KM . ",round(company_an_settings.radius*" . Constants::KM_2_MI . ",2),company_an_settings.radius) as radius")

                )->where('companies.id', $this->comId)
                ->first();
            DB::delete("delete from c2c_match where from_com_id = {$this->comId} or to_com_id={$this->comId};");
            DB::insert(
                "insert into c2c_match (from_com_id, to_com_id) 
                    SELECT companies.id,{$company->id} from company_an_settings left join companies on companies.id=company_an_settings.company_id where company_an_settings.company_id != {$company->id} and
                    ((company_an_settings.unit = " . Constants::UNIT_MI . " and (company_an_settings.radius) > (" . Constants::MI_EARTH_R . " * acos(cos(radians({$company->lat})) * cos(radians(companies.lat)) * cos(radians
                               (companies.lng) - radians({$company->lng})) + sin(radians({$company->lat})) * sin(radians(companies.lat)))))
                               or (company_an_settings.unit = " . Constants::UNIT_KM . " AND (company_an_settings.radius*" . Constants::KM_2_MI . " ) > (" . Constants::MI_EARTH_R . " * acos(cos(radians({$company->lat})) * cos(radians(companies.lat)) * cos(radians
                               (companies.lng) - radians({$company->lng})) + sin(radians({$company->lat})) * sin(radians(companies.lat))))))
                               "
            );

            DB::insert(
                "insert into c2c_match(from_com_id, to_com_id)
                    SELECT {$company->id},companies.id from company_an_settings 
                    left join companies on companies.id=company_an_settings.company_id 
                    where company_an_settings.company_id != {$company->id} and
                    {$company->radius}  > (".Constants::MI_EARTH_R . " * acos(cos(radians({$company->lat})) * cos(radians(companies.lat)) * cos(radians
                               (companies.lng) - radians({$company->lng})) + sin(radians({$company->lat})) * sin(radians(companies.lat))))
                               "
            );
        });

    }
}
