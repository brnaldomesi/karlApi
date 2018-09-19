<?php

namespace App\Jobs;

use App\Model\RunningError;
use App\QueueName;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Log;

class SendEmailResetAdminPasswordJob extends Job
{
    /**
     * Create a new job instance.
     *
     * @return void
     */

    private $email;
    private $password;
    private $lang;

    public function __construct($email, $password,$lang)
    {
        //
        $this->email = $email;
        $this->password = $password;
        $this->lang = $lang;
        $this->onQueue(QueueName::EmailResetAdminPassword);
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
        \app('translator')->setLocale($this->lang);
        $view = view('re_set_ad_pwd_email', ["password" => $this->password,"type"=>2])->render();
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
