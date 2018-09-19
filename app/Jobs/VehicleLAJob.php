<?php

namespace App\Jobs;

use App\Model\Car;
use App\Model\LnAskRecord;
use App\Model\LnProvideRecord;
use App\QueueName;
use DB;

class VehicleLAJob extends Job
{
    /**
     * Create a new job instance.
     */
    private $comId;

    public function __construct($comId)
    {
        $this->comId = $comId;
        $this->onQueue(QueueName::CarAP);
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function work()
    {
        DB::transaction(function () {
            $cars = Car::where("company_id", $this->comId)->get();
            LnAskRecord::where("company_id", $this->comId)
                ->update(["secret" => LnAskRecord::SECRET_NO]);
            LnAskRecord::where("company_id", $this->comId)
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
            LnProvideRecord::where("company_id", $this->comId)
                ->update(["secret" => LnProvideRecord::SECRET_NO]);
            LnProvideRecord::where("company_id", $this->comId)
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
        });

    }
}
