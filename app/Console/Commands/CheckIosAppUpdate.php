<?php
/**
 * Created by PhpStorm.
 * User: lqh
 * Date: 2017/3/7
 * Time: 上午11:38
 */

namespace App\Console\Commands;


use App\Model\CompanyAnnex;
use Curl\Curl;
use Illuminate\Console\Command;

class CheckIosAppUpdate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update:check';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'check ios app version';

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
        \DB::transaction(function(){
            $companies = CompanyAnnex::
            select('company_id',
                'ios_id',
                'ios_version',
                'android_version',
                "pkg_name")
                ->get();
            foreach ($companies as $company) {
                $update=[];
                $curl = new Curl();
                try{
                    if(!empty($company->ios_id)){
                        $result = $curl->get("https://itunes.apple.com/lookup?id=".$company->ios_id);
                        $result = json_decode($result,true);
                        $iosChkVer = $result['results'][0]['version'];
                        $iosVersion = CompanyAnnex::appVersionCompare($company->ios_version,$iosChkVer);
                        if($iosChkVer == $iosVersion){
                            $update["ios_version"]=$iosVersion;
                        }
                    }
                }catch(\Exception $ex){
                }
                try{
                    if(!empty($company->pkg_name)){
                        $androidResult = $curl->get("https://play.google.com/store/apps/details?id=".$company->pkg_name);
                        $andVerCheck= [];
                        preg_match("/\"softwareVersion\"\\W*([\\d\\.]+)/",$androidResult,$andVerCheck,PREG_OFFSET_CAPTURE);
                        $andChkVer = $andVerCheck[1][0];
                        $andChkVer = substr($andChkVer,0,strlen($andChkVer)-2);
                        $andVersion = CompanyAnnex::appVersionCompare($company->android_version,$andChkVer);
                        if($andChkVer == $andVersion){
                            $update["android_version"]=$andChkVer;
                        }
                    }
                }catch(\Exception $ex){
                }
                if(sizeof($update)>0){
                    CompanyAnnex::where("company_id",$company->company_id)
                        ->update($update);
                }

            }
        });
    }
}