<?php

use App\Model\Car;
use App\Model\LnAskRecord;
use App\Model\LnProvideRecord;
use App\Model\Offer;
use Illuminate\Support\Facades\DB;

/**
 * Created by PhpStorm.
 * User: liqihai
 * Date: 16/9/19
 * Time: 下午3:31
 */

class UpdateLNUnit extends BaseSeeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    protected function task()
    {
        DB::transaction(function() {
            DB::update("update ln_ask_records set needed=1;");
            DB::update("update ln_provide_records set provide=1;");
            $companies = \App\Model\Company::all();
            foreach ( $companies as $company) {
                $cars = Car::where("company_id", $company->id)->get();
                LnAskRecord::where("company_id", $company->id)
                    ->update(["secret" => LnAskRecord::SECRET_NO]);
                LnAskRecord::where("company_id", $company->id)
                    ->where("secret", LnAskRecord::SECRET_NO)
                    ->where("needed", LnAskRecord::NEEDED_NO)
                    ->delete();

                foreach ($cars as $car) {
                    $record = LnAskRecord::firstOrNew([
                        "company_id" => $car->company_id,
                        "car_model_id" => $car->car_model_id
                    ]);
                    $record->secret = LnAskRecord::SECRET_ASK;
                    $record->save();
                }
                LnProvideRecord::where("company_id", $company->id)
                    ->update(["secret" => LnProvideRecord::SECRET_NO]);
                LnProvideRecord::where("company_id", $company->id)
                    ->where("secret", LnProvideRecord::SECRET_NO)
                    ->where("provide", LnProvideRecord::PROVIDE_NO)
                    ->delete();
                foreach ($cars as $car) {
                    $record = LnProvideRecord::firstOrNew([
                        'company_id' => $car->company_id,
                        "car_id" => $car->id
                    ]);
                    $record->secret = LnProvideRecord::SECRET_ASK;
                    $record->save();
                }
            }
        });

    }
}
