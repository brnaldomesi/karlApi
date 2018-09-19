<?php

namespace App\Jobs;

use App\Model\Company;
use App\Model\Customer;
use App\Model\RunningError;
use App\QueueName;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Log;

class SendEmailResetPassengerPasswordJob extends Job
{
    /**
     * Create a new job instance.
     *
     * @return void
     */

    private $email;
    private $password;
    private $company_id;

    public function __construct($email, $password , $company_id)
    {
        //
        $this->email = $email;
        $this->password = $password;
        $this->company_id = $company_id;
        $this->onQueue(QueueName::EmailResetPassengerPassword);
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function work()
    {
        //
        $host = $_SERVER['SMTP_HOST'];
        $email = $_SERVER['SMTP_EMAIL'];
        $pwd = $_SERVER['SMTP_PWD'];
        $port = $_SERVER['SMTP_PORT'];
        $mail = $this->initPhpEmailBox($host, $email, $pwd, $port);
        $info = Company::where('id',$this->company_id)->first();
        $info->password=$this->password;
        $info->email=$this->email;
        $info->android=$_SERVER['local_url'] . "/app/company/{$this->company_id}/android";
        $info->ios=$_SERVER['local_url'] . "/app/company/{$this->company_id}/ios";
        $customer = Customer::leftJoin("users","users.id","=","customers.user_id")
            ->where("users.company_id",$this->company_id)
            ->where("users.email",$this->email)
            ->select("users.lang")->first();
        if(empty($customer)){
            $lang = "en";
        }else{
            $lang = $customer->lang;
        }
        if(strtolower($lang) == 'fr'){
            $info->android_app = $_SERVER['local_url']."/imgs/common/google_fr_app";
            $info->ios_app = $_SERVER['local_url']."/imgs/common/ios_fr_app";
        }else{
            $info->android_app = $_SERVER['local_url']."/imgs/common/google_app";
            $info->ios_app = $_SERVER['local_url']."/imgs/common/ios_app";
        }

        \app('translator')->setLocale($lang);
        $view = view('re_set_pa_pwd_email', ["info" => $info,"type"=>2])->render();
        try {
            $mail->setFrom($email, 'KARL Support');
            $mail->Subject = Lang::get('password.pwdTitle');
            $mail->addAddress($this->email);
            $mail->isHTML(true);
            $mail->msgHTML($view);
            if (!$mail->send()) {
                Log::info("SEND EMAIL " . $mail->ErrorInfo);
            } else {
                RunningError::recordRunningError(
                    RunningError::TYPE_EMAIL,
                    RunningError::STATE_SUCCESS,
                    "send admin success"
                );
            }
        } catch (\Exception $ex) {
            Log::info($ex->getMessage());
        }
    }
}
