<?php

namespace App\Jobs;

use App\Method\GeoLocationAlgorithm;
use App\Method\UrlSpell;
use App\Method\MethodAlgorithm;
use App\Model\Admin;
use App\Model\Booking;
use App\Model\Company;
use App\Model\RunningError;
use App\QueueName;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SendTestEmailJob extends Job
{
    /**
     * Create a new job instance.
     */

    private $email;

    public function __construct($email)
    {
        //
        $this->email = $email;
        $this->onQueue('email_test');
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function work()
    {
        $host =   $_SERVER['SMTP_HOST'];
        $port =   $_SERVER['SMTP_PORT'];
        $email =   $_SERVER['SMTP_EMAIL'];
        $pwd =   $_SERVER['SMTP_PWD'];
        echo "email is ".$email."\n";
        echo "email host is ".$host."\n";
        echo "email pwd is ".$pwd."\n";
        echo "email port is ".$port."\n";


        $mail = $this->initPhpEmailBox($host, $email, $pwd, $port,true);

        $view = view("content")->render();

        try {
            $mail->setFrom($email, 'KARL Support');
            $mail->Subject = 'There is an new booking for your company';
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
