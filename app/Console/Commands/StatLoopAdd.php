<?php

namespace App\Console\Commands;

use App\Method\CalendarAlgorithm;
use App\Method\MethodAlgorithm;
use Faker\Provider\cs_CZ\DateTime;
use Illuminate\Support\Facades\DB;
use Illuminate\Console\Command;

class StatLoopAdd extends Command
{

    /**
     * 预定推送,
     * 1.乘客在24小时内有订单即将执行,
     * 2.司机和乘客在1小时内有订单即将执行,
     */
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'booking:stat';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'add booking stat day for company';

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
        DB::transaction(function () {
            $companies = DB::select("select
  a.company_id,unix_timestamp(a.stat_date) as stat,
  ifnull(companies.timezone,'".$_SERVER['TIME_ZONE']."') as timezone
from (select company_id , max(stat_date) as stat_date from `booking_day_statistics` group by `company_id`) as a LEFT JOIN companies on a.company_id=companies.id;");
            foreach ($companies as $company) {
                $companyTimezone = new \DateTimeZone($company->timezone);
                $nowTime = new \DateTime("@".time());
                $nowTime->setTimezone($companyTimezone);
                date_add($nowTime, date_interval_create_from_date_string("3 months"));
                echo 'create days for company '.$company->company_id ."\n";
                if($company->stat >=
                        $nowTime->format('U')){
                    continue;
                }
                MethodAlgorithm
                    ::createStatDayForCompany(
                        $company->company_id,$company->stat,
                        $nowTime->format('U'),
                        $company->timezone
                        );
            }
        });
    }
}
