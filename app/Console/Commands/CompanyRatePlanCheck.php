<?php

namespace App\Console\Commands;

use App\Method\MethodAlgorithm;
use App\Model\BookingDayStatistic;
use App\Model\Company;
use App\Model\ComRateRule;
use App\Model\RateRule;
use Illuminate\Support\Facades\DB;
use Illuminate\Console\Command;

class CompanyRatePlanCheck extends Command
{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'rate:plan';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'check and set company rate';

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
        DB::transaction(function(){
            //1.获取rate过期的公司
            $now = time();
            $companies = Company::where(DB::raw("unix_timestamp(rit)"),"<",$now)
                ->get();
            //2.判断是否有plan
            foreach ($companies as $company) {
                $rule = ComRateRule::where('company_id',$company->id)
                    ->where("start_time",'<=',$now)
                    ->where("end_time",'>',$now)
                    ->first();
                if(empty($rule)) {
                    continue;
                }
                $company->rate = $rule->rate;
                $company->rit = MethodAlgorithm::formatTimestampToDate($rule->end_time);
                $company->save();
                $key = intval($companies->search($company));
                $companies->pull($key);
            }
            $companies->every(1);
            //3.如果没有plan，计算订单数。
            foreach ($companies as $company) {
                $timezone = new \DateTimeZone($company->timezone);
                $companyDate = new \DateTime("@{$now}");
                $companyDate->setTimezone($timezone);
                $month = $companyDate->format('n');
                $year = $companyDate->format('Y');
                if($month == 1){
                    $chkMonth =12;
                    $chkYear = $year-1;
                }else{
                    $chkMonth =$month-1;
                    $chkYear = $year;
                }
                $count = BookingDayStatistic::where("company_id",$company->id)
                    ->where('stat_month',$chkMonth)
                    ->where('stat_year',$chkYear)
                    ->select(DB::raw("sum(completed_bookings) as total"))
                    ->groupBy('stat_month','stat_year')
                    ->first();
                $sum = empty($count)?0:$count->total;
                $rule = RateRule::where("type",RateRule::RULE_TYPE_RATES)
                    ->where('invl_start',"<=",$sum)
                    ->where('invl_end',">",$sum)
                    ->first();
                if($month==12){
                    $endMonth =1;
                    $endYear = $year+1;
                }else{
                    $endMonth =$month+1;
                    $endYear = $year;
                }
                $company->rate = empty($rule->rate)?0.1:$rule->rate;
                $companyDate->setDate($endYear,$endMonth,1);
                $companyDate->setTime(0,0,0);
                $company->rit=MethodAlgorithm::formatTimestampToDate($companyDate->getTimestamp());
                $company->save();
            }
        });
    }
}
