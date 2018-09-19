<?php

namespace App\Console\Commands;

use App\Jobs\SendEmailAdminPasswordJob;
use Illuminate\Console\Command;

class CheckCalendarsDst extends Command
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
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        dispatch(new SendEmailAdminPasswordJob("844718628@qq.com","123456"));
        dispatch(new SendEmailAdminPasswordJob("weilili0209@gmail.com","123456"));
        dispatch(new SendEmailAdminPasswordJob("wll20161026@gmail.com","123456"));
    }
}
