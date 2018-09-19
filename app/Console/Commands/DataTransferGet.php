<?php

namespace App\Console\Commands;

use App\DataList;
use App\Jobs\PushCustomerJob;
use App\Jobs\PushDriverJob;
use App\Jobs\SendEmailAdminPasswordJob;
use App\Model\Booking;
use Illuminate\Support\Facades\DB;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class DataTransferGet extends Command
{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'data:get {--table=All}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'get all data from data source';

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
        $table = $this->option('table');
        DB::transaction(function () use($table) {
            if($table=='All'){
                foreach (DataList::$TABLES as $table) {
                    echo "start get {$table} \n";
                    $data = DB::table($table)->get();
                    File::put(storage_path("/data/{$table}.json"), json_encode($data));
                    echo "end get {$table} \n";
                }
            }else{
                echo "start get {$table} \n";
                $data = DB::table($table)->get();
                File::put(storage_path("/data/{$table}.json"), json_encode($data));
                echo "end get {$table} \n";
            }
        });
    }
}
