<?php

use App\Constants;
use App\Model\Offer;
use Illuminate\Support\Facades\DB;

/**
 * Created by PhpStorm.
 * User: liqihai
 * Date: 16/9/19
 * Time: 下午3:31
 */
class CreateC2CMatch extends BaseSeeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    protected function task()
    {
        DB::transaction(function () {
//            $offers = Offer::select(
//                "id",
//                "company_id",
//                "d_lat",
//                "d_lng",
//                DB::raw("if(unit=".Offer::UNIT_KM.",round(d_radius*".Constants::KM_2_MI.",2),d_radius) as d_radius")
//                )->get();
//            foreach ($offers as $offer) {
//                DB::insert(
//                    "insert into o2o_match(company_id, from_offer_id, to_offer_id)
//                    SELECT {$offer->company_id},{$offer->id},id from offers where company_id != {$offer->company_id} and
//                    ((offers.unit = ".Offer::UNIT_MI." and (offers.d_radius + {$offer->d_radius}) < (" . Constants::MI_EARTH_R . " * acos(cos(radians({$offer->d_lat})) * cos(radians(offers.d_lat)) * cos(radians
//                               (offers.d_lng) - radians({$offer->d_lng})) + sin(radians({$offer->d_lat})) * sin(radians(offers.d_lat)))))
//                               or (offers.unit = ".Offer::UNIT_KM." AND (offers.d_radius*".Constants::KM_2_MI." + {$offer->d_radius}) < (" . Constants::MI_EARTH_R . " * acos(cos(radians({$offer->d_lat})) * cos(radians(offers.d_lat)) * cos(radians
//                               (offers.d_lng) - radians({$offer->d_lng})) + sin(radians({$offer->d_lat})) * sin(radians(offers.d_lat))))))
//                               "
//                );
//            }
            $companies = \App\Model\Company::leftjoin('company_an_settings', "company_an_settings.company_id", "=", "companies.id")
                ->leftjoin('company_settings', "company_settings.company_id", "=", "companies.id")
                ->select(
                    "companies.id",
                    "lat",
                    "lng",
                    DB::raw("if(company_an_settings.unit=" . Constants::UNIT_KM . ",round(company_an_settings.radius*" . Constants::KM_2_MI . ",2),company_an_settings.radius) as radius")
                )->get();
            foreach ($companies as $company) {
                DB::insert(
                    "insert into c2c_match(from_com_id, to_com_id)
                    SELECT {$company->id},companies.id from company_an_settings 
                    left join companies on companies.id=company_an_settings.company_id 
                    where company_an_settings.company_id != {$company->id} and
                    {$company->radius}  > (".Constants::MI_EARTH_R . " * acos(cos(radians({$company->lat})) * cos(radians(companies.lat)) * cos(radians
                               (companies.lng) - radians({$company->lng})) + sin(radians({$company->lat})) * sin(radians(companies.lat))))
                               "
                );
            }
        });
    }
}
