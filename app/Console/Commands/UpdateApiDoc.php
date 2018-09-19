<?php

namespace App\Console\Commands;

use App\Jobs\PushBookingUpdateJob;
use App\Jobs\PushCustomerJob;
use App\Jobs\PushDriverJob;
use App\Jobs\SendEmailAdminPasswordJob;
use App\Model\Booking;
use Illuminate\Support\Facades\DB;
use Illuminate\Console\Command;

class UpdateApiDoc extends Command
{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'doc:update';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'update api documents';

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
        system("php vendor/zircote/swagger-php/bin/swagger app/Http/Controllers/v1 -o storage/assets/");
    }
}
