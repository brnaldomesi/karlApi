<?php

namespace App\Jobs;

use App\Model\Asst;
use App\Model\RunningError;
use App\QueueName;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Log;

class SendEmailResetAsstPasswordJob extends Job
{
    /**
     * Create a new job instance.
     *
     * @return void
     */

    private $email;
    private $password;

    public function __construct($email, $password)
    {
        $this->email = $email;
        $this->password = $password;
        $this->onQueue(QueueName::EmailSalePassword);
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

        $asst = Asst::leftJoin("users","users.id","=","assts.user_id")
            ->where("users.company_id",0)
            ->where("users.email",$this->email)
            ->select("users.lang","assts.asst_id")
            ->first();
        if(empty($asst)){
            $lang = "en";
        }else{
            $lang = $asst->lang;
        }
        \app('translator')->setLocale($lang);
        $view = view('re_set_as_pwd_email', ["asstId" => $asst->asst_id, "type" => 2,'pwd'=>$this->password])->render();


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
