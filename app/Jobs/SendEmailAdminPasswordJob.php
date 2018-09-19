<?php

namespace App\Jobs;

use App\Model\RunningError;
use App\QueueName;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Log;

class SendEmailAdminPasswordJob extends Job
{
    /**
     * Create a new job instance.
     *
     * @return void
     */
    private $email;
    private $password;
    private $lang;

    public function __construct($email,$password,$lang='en')
    {
        //
        $this->email = $email;
        $this->password = $password;
        $this->lang = $lang;
        $this->onQueue(QueueName::EmailAdminPassword);

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
        $mail = $this->initPhpEmailBox($host,$email , $pwd,$port);
        \app('translator')->setLocale($this->lang);
        $view = view('re_set_ad_pwd_email',["password"=>$this->password,"type"=>1]);
        $mail->setFrom($email,'KARL Support');
        $mail->Subject = Lang::get('password.pwdTitle');
        $mail->addAddress($this->email);
        $mail->addBCC($_SERVER['BCC_EMAIL']);
        $mail->isHTML(true);
        $mail->msgHTML($view);
        try{
            if(!$mail->send()) {
                Log::info('send admin email error'.$mail->ErrorInfo);
            }
        }catch(\Exception $ex){
            Log::info('error is '.$ex);
        }
    }
}
