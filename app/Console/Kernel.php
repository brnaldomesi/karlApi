<?php

namespace App\Console;


use Illuminate\Console\Scheduling\Schedule;
use Laravel\Lumen\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        Commands\SendingSettleEmail::class,
        Commands\OrderPushCheck::class,
        Commands\CalenderLoopAdd::class,
        Commands\CreateErrorCodeJs::class,
        Commands\MoveLogFile::class,
        Commands\QueueCheck::class,
        Commands\TransRunner::class,
        Commands\DataTransferGet::class,
        Commands\DataTransferPut::class,
        Commands\StatLoopAdd::class,
        Commands\MakeModel::class,
        Commands\DeleteCompany::class,
        Commands\EmailTest::class,
        Commands\FinanceConverter::class,
        Commands\GetAdminAccount::class,
        Commands\CompanyRatePlanCheck::class,
        Commands\CheckIosAppUpdate::class,
        Commands\NewCreatedCompanies::class,
        Commands\UpdateApiDoc::class
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        //每天美国西部时间早上九点发送email
        $sendTime = $_SERVER['SETTLE_TIME'];
        $timezone = $_SERVER['TIME_ZONE'];
        $time = new \DateTime("@".time());
        $time->setTimezone(new \DateTimeZone($timezone));
        $receiveTimezone = $time->getOffset()/3600;
        $scheduleTime=$sendTime-$receiveTimezone;
        if($scheduleTime>24){
            $scheduleTime = $scheduleTime - 24;
        }
        $schedule->command('email:settle')->dailyAt($scheduleTime.':00');
        $schedule->command('calendar:loop')->dailyAt((0-$receiveTimezone).':00');
        $schedule->command('order:push')->everyTenMinutes();
        $schedule->command('queue:check')->everyMinute();
        $schedule->command('rate:plan')->hourly();
        $schedule->command('log:move')->dailyAt("00:30");
        $schedule->command('ccy:cvt')->dailyAt("00:00");
        $schedule->command('booking:stat')->daily();
        $schedule->command('update:check')->daily();
        $schedule->command('trans')->dailyAt("03:00");
        $schedule->command('trans')->dailyAt("15:00");
    }


}
