<?php

namespace App\Console\Commands;

use App\Jobs\PushBookingUpdateJob;
use App\Jobs\PushCustomerJob;
use App\Jobs\PushDriverJob;
use App\Jobs\SendEmailAdminPasswordJob;
use App\Model\Admin;
use App\Model\Booking;
use Illuminate\Support\Facades\DB;
use Illuminate\Console\Command;

class GetAdminAccount extends Command
{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'admin:pwd {--cid=1}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'get company admin account and pwd';

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
     */
    public function handle()
    {
        $companyId = $this->option('cid');

        $result = Admin::leftjoin("users","admins.user_id","=","users.id")
            ->where('users.company_id',$companyId)
            ->select('users.username','users.email','users.password')
            ->first();
        echo $result;
    }
}
