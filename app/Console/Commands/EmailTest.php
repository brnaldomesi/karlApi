<?php

namespace App\Console\Commands;

use App\Jobs\PushBookingUpdateJob;
use App\Jobs\PushCustomerJob;
use App\Jobs\PushDriverJob;
use App\Jobs\SendEmailAdminPasswordJob;
use App\Model\Booking;
use Illuminate\Support\Facades\DB;
use Illuminate\Console\Command;

class EmailTest extends Command
{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'email:pwd';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'send password email';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
//        dispatch(new SendEmailAdminPasswordJob("844718628@qq.com","123456"));
//        dispatch(new SendEmailAdminPasswordJob("weilili0209@gmail.com","123456"));
//        dispatch(new SendEmailAdminPasswordJob("liqihai1987@gmail.com","123456"));
        dispatch(new PushBookingUpdateJob(7,237,273));

    }
}
