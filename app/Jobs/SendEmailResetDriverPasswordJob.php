<?php

namespace App\Jobs;

use App\Model\Company;
use App\Model\Driver;
use App\Model\RunningError;
use App\QueueName;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Log;

class SendEmailResetDriverPasswordJob extends Job
{
    /**
     * Create a new job instance.
     *
     * @return void
     */

    private $email;
    private $password;
    private $companyId;

    public function __construct($email, $password, $companyId)
    {
        $this->email = $email;
        $this->password = $password;
        $this->companyId = $companyId;
        $this->onQueue(QueueName::EmailResetDriverPassword);
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function work()
    {
        $host = $_SERVER['SMTP_HOST'];
        $email = $_SERVER['SMTP_EMAIL'];
        $pwd = $_SERVER['SMTP_PWD'];
        $port = $_SERVER['SMTP_PORT'];
        $mail = $this->initPhpEmailBox($host, $email, $pwd, $port);

        $driver = Driver::leftJoin("users","users.id","=","drivers.user_id")
            ->where("users.company_id",$this->companyId)
            ->where("users.email",$this->email)
            ->select("users.lang")->first();
        if(empty($driver)){
            $lang = "en";
        }else{
            $lang = $driver->lang;
        }
        $info = Company::where("id",$this->companyId)
            ->first();
        $info->email=$this->email;
        $info->password=$this->password;
        if(strtolower($lang) == 'fr'){
            $info->android_app = $_SERVER['local_url']."/imgs/common/google_fr_app";
            $info->ios_app = $_SERVER['local_url']."/imgs/common/ios_fr_app";
        }else{
            $info->android_app = $_SERVER['local_url']."/imgs/common/google_app";
            $info->ios_app = $_SERVER['local_url']."/imgs/common/ios_app";
        }
        \app('translator')->setLocale($lang);
        $view = view('re_set_dr_pwd_email'
            , ["type" => 2, "info" =>$info])->render();

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
