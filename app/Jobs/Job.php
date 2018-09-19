<?php

namespace App\Jobs;

use App\Constants;
use App\Model\Customer;
use App\Model\Driver;
use App\Method\PushHandler;
use App\Model\RunningError;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

abstract class Job implements ShouldQueue
{
    /*
    |--------------------------------------------------------------------------
    | Queueable Jobs
    |--------------------------------------------------------------------------
    |
    | This job base class provides a central location to place any logic that
    | is shared across all of your jobs. The trait included with the class
    | provides access to the "queueOn" and "delay" queue helper methods.
    |
    */

    use InteractsWithQueue, Queueable, SerializesModels;


    protected function initPhpEmailBox($host, $username, $pwd, $port, $needDebug = false)
    {
        $mail = new \PHPMailer();
        if ($needDebug) {
            $mail->CharSet = "utf-8";
            $mail->SMTPDebug = 2;
            $mail->Debugoutput = 'html';
        }
        $mail->isSMTP();
        $mail->Timeout=30;
        $mail->Host = $host;
        $mail->SMTPAuth = true;
        $mail->Username = $username;
        $mail->Password = $pwd;
        $mail->SMTPSecure = 'tls';
        $mail->Port = $port;

        return $mail;
    }


    protected function pushNotification($target_id,$type, $message)
    {
        if($type == Constants::DRIVER_PUSH) {
            $company = Driver::leftjoin("users","users.id",'=','drivers.user_id')
                ->leftjoin("companies","companies.id",'=','users.company_id')
                ->where('drivers.id',$target_id)
                ->whereRaw('users.device_token is not null')
                ->select("users.device_token","users.lang")//,"drivers.device_token")
                ->first();
            if(empty($company)){
                return "";
            }
            $company->push_profile = $_SERVER['driver_push_profile'];
            $company->push_api_token = $_SERVER['driver_push_token'];
            $company->name="KARL";
        }elseif($type == Constants::CUSTOMER_PUSH){
            $company = Customer::leftjoin("users","users.id",'=','customers.user_id')
                ->leftjoin("company_push_config","company_push_config.company_id",'=','users.company_id')
                ->leftjoin('companies','companies.id',"=","users.company_id")
                ->where('customers.id',$target_id)
                ->where('company_push_config.push_type',Constants::CUSTOMER_PUSH)
                ->whereRaw('users.device_token is not null')
                ->select("company_push_config.push_profile","companies.name","company_push_config.push_api_token","users.device_token","users.lang")//,"customers.device_token")
                ->first();
        }else{
        }

        if(!empty($company)){
            $pushHandler = new PushHandler($company->push_profile,$company->push_api_token);

            $pushHandler->notify(array($company->device_token),array("en"=>$message));

            return true;
        }
        return null;
    }

    public function handle(){
        try{
            $this->work();
        }catch(\Exception $ex){
            \Log::error("job error : ".$ex);
        }
    }

    public abstract function work();
}
