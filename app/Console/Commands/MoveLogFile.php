<?php

namespace App\Console\Commands;

use App\Method\MethodAlgorithm;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class MoveLogFile extends Command
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
    protected $signature = 'log:move';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'move log file';

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
        $time = MethodAlgorithm::formatTimestampToDate(time(),"Y-m-d");
        $tempFilePath = storage_path("logs-".$time);
        $logFilePath = storage_path("logs/lumen.log");
        if(!file_exists($tempFilePath)){
            mkdir($tempFilePath);
        }
        if(file_exists($logFilePath)){
            rename($logFilePath,$tempFilePath."/lumen.log");
        }
        File::put($logFilePath,"created at ".MethodAlgorithm::formatTimestampToDate(time()));
        system("chown ec2-user:ec2-user ".$logFilePath);
        system("chmod 777 ".$logFilePath);
    }
}
