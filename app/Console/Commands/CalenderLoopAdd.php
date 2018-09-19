<?php

namespace App\Console\Commands;

use App\Method\CalendarAlgorithm;
use Illuminate\Support\Facades\DB;
use Illuminate\Console\Command;

class CalenderLoopAdd extends Command
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
    protected $signature = 'calendar:loop';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'add calender for repeat event';

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
        DB::transaction(function (){
            CalendarAlgorithm::getCalendar()->loopAddRecurringEvents();
        });
    }
}
