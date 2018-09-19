<?php

namespace App\Console\Commands;

use App\ErrorCode;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class CreateErrorCodeJs extends Command
{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:code';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'make file of js error code';

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
     */
    public function handle()
    {
        $errorCode = ErrorCode::getErrorCodes();
        $file="ErrorCode={ \n\tcreate:".time().", \n";
        foreach ($errorCode as $code) {
            $file = $file."\t{$code['code']}:\"{$code['msg']}\", \n";
        }
        $file = substr($file, 0, -3);
        $file =$file."\n};";
        File::put(storage_path("ErrorCode.js"),$file);
        system("mv storage/ErrorCode.js ../../dashboard/trunk/js/ &&
         cd ../../dashboard/trunk/js && 
         svn ci ErrorCode.js -m 'upload error code' ",$result);
        echo $result;
//        File::(storage_path("ErrorCode.js"),"~/Documents/workspace/inov/a4c/dashboard/trunk/js/ErrorCode.js");
    }
}
