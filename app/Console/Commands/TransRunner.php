<?php

namespace App\Console\Commands;

use App\Jobs\PushBookingUpdateJob;
use App\Jobs\PushCustomerJob;
use App\Jobs\PushDriverJob;
use App\Jobs\SendEmailAdminPasswordJob;
use App\Method\StripeMethod;
use App\Model\Booking;
use App\Model\TransRecord;
use Illuminate\Support\Facades\DB;
use Illuminate\Console\Command;
use Stripe\Stripe;
use Stripe\Transfer;

class TransRunner extends Command
{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'trans';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'transfer money to limo company';

    /**
     * Create a new command instance.
     *
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     */
    public function handle()
    {
        $now = time();
        $records = TransRecord::leftjoin('companies',"companies.id","=","trans_records.company_id")
            ->where("trans_records.trans_type",TransRecord::TYPE_WAIT)
            ->where("trans_records.available_on","<=",$now)
            ->select(
                "trans_records.trans_amount",
                "trans_records.trans_balance_id",
                "trans_records.id",
                "trans_records.trans_ccy",
                "companies.stripe_acct_id"
            )
            ->get();
        Stripe::setApiKey($_SERVER['STRIP_S_KEY']);
        foreach ($records as $record) {
            try{
                $transfer = Transfer::create([
                    "amount" => ($record->trans_amount)*100,
                    "currency" => $record->trans_ccy,
                    "destination" => $record->stripe_acct_id,
                ]);
                TransRecord::where("id",$record->id)
                    ->update(["trans_type"=>TransRecord::TYPE_SUCCESS,"trans_id"=>$transfer->id]);
            }catch(\Exception $ex){
                TransRecord::where("id",$record->id)
                    ->update(["trans_type"=>TransRecord::TYPE_FAULT]);
                \Log::error($ex->getMessage());
            }
        }
    }
}
