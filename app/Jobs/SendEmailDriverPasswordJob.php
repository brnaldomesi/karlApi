<?php

namespace App\Jobs;

use App\Model\Company;
use App\Model\Driver;
use App\Model\RunningError;
use App\QueueName;
use App\Method\UrlSpell;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Lang;

class SendEmailDriverPasswordJob extends Job
{
    /**
     * Create a new job instance.
     *
     * @return void
     */
    private $email;
    private $password;
    private $companyId;

    public function __construct($email,$company_id,$password)
    {
        $this->email = $email;
        $this->password = $password;
        $this->companyId = $company_id;
        $this->onQueue(QueueName::EmailDriverPassword);

    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function work()
    {

        $driver = Driver::leftJoin("users","users.id","=","drivers.user_id")
            ->where("users.company_id",$this->companyId)
            ->where("users.email",$this->email)
            ->select("users.lang")->first();
        if(empty($driver)){
            $lang = "en";
        }else{
            $lang = $driver->lang;
        }
        $info = Company::where('id', $this->companyId)
            ->select(
                DB::raw(UrlSpell::getUrlSpell()->getCompaniesLogoByNameInDB('company_logo')),
                'name'
                )->first();

        $info->password = $this->password;
        $info->email = $this->email;
        $info->company_name = $info->name;
        if(strtolower($lang) == 'fr'){
            $info->android_app = $_SERVER['local_url']."/imgs/common/google_fr_app";
            $info->ios_app = $_SERVER['local_url']."/imgs/common/ios_fr_app";
        }else{
            $info->android_app = $_SERVER['local_url']."/imgs/common/google_app";
            $info->ios_app = $_SERVER['local_url']."/imgs/common/ios_app";
        }

        \app('translator')->setLocale($lang);
        $subTitle = Lang::get('password.welcome');
        $view = view("re_set_dr_pwd_email", ["info" =>$info,"type"=>1])->render();

        $host = $_SERVER['SMTP_HOST'];
        $email = $_SERVER['SMTP_EMAIL'];
        $pwd = $_SERVER['SMTP_PWD'];
        $port = $_SERVER['SMTP_PORT'];
        $mail = $this->initPhpEmailBox($host, $email, $pwd, $port);

        try {
            $mail->setFrom($email, 'KARL Support');
            $mail->Subject = $subTitle;
            $mail->addAddress($this->email);
            $mail->isHTML(true);
            $mail->msgHTML($view);
            if (!$mail->send()) {
                \Log::error($mail->ErrorInfo);
            } else {
                RunningError::recordRunningError(
                    RunningError::TYPE_EMAIL,
                    RunningError::STATE_SUCCESS,
                    "send customer success"
                );
            }
        } catch (\Exception $ex) {
            Log::error($ex);
        }
    }
}
