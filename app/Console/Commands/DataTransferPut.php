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

class DataTransferPut extends Command
{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'data:put {--table=all}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'put all data from data source';

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
     */
    public function handle()
    {
        $table = $this->option('table');
        DB::transaction(function () use ($table) {
            if($table=='all'){
                foreach (DataList::$TABLES as $table) {
                    $this->insertTable($table);
                }
            }else{
                $this->insertTable($table);
            }
        });
    }


    private function insertTable($table)
    {
        echo "start put {$table} \n";

        DB::table($table)->delete();
        $data = File::get(storage_path("/data/{$table}.json"));
        $dataInfos = json_decode($data, true);
        $count = count($dataInfos);
        if ( $count > 1024) {
            $max = floor($count/1024);
            for ($i=0;$i<$max;$i++){
                $datas = array_slice($dataInfos,1024*$i,1024);
                DB::table($table)->insert(
                    $datas
                );
            }
            $datas = array_slice($dataInfos,1024*$max,($count-1024*$max));
            DB::table($table)->insert(
                $datas
            );
        } else {
            DB::table($table)->insert(
                $dataInfos
            );
        }
        echo "end put {$table} \n";
    }
}
