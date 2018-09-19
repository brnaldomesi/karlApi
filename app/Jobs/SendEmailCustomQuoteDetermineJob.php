<?php

namespace App\Jobs;

use App\Model\Booking;
use App\Model\Company;
use App\Model\Customer;
use App\Model\RunningError;
use App\QueueName;
use Illuminate\Support\Facades\Lang;

class SendEmailCustomQuoteDetermineJob extends Job
{

    protected $booking;

    /**
     * Create a new job instance.
     * @param $booking Booking
     * @return void
     */
    public function __construct(Booking $booking)
    {
//        echo $booking;
        $this->booking = $booking;
        $this->onQueue(QueueName::EmailQuoteDetermine);

    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function work()
    {

        $customer = Customer::leftjoin("users", 'users.id', "=", 'customers.user_id')
            ->where('customers.id', $this->booking->customer_id)
            ->select('users.gender', 'users.first_name',
                'users.last_name', 'users.email',"users.lang")
            ->first();
        //send email
        $requestUrl = $_SERVER['local_url'] .
            "/1/bookings/" . md5($this->booking->id) . "/determine/" . md5($this->booking->custom_auth_code)."/".$customer->lang;
        $logoUrl = $_SERVER['local_url'] .
            "/1/companies/" . $this->booking->company_id . "/logo/";


        $company = Company::where('id', $this->booking->company_id)->first();

        $host = $company->email_host;
        $email = $company->email;
        $pwd = base64_decode($company->email_password);
        $port = $company->email_port;

        if (empty($host) || empty($email) || empty($pwd) || empty($port))
        {
            $host = $_SERVER['SMTP_HOST'];
            $email = $_SERVER['SMTP_EMAIL'];
            $pwd = $_SERVER['SMTP_PWD'];
            $port = $_SERVER['SMTP_PORT'];
        }

        $mail = $this->initPhpEmailBox($host, $email,
            $pwd, $port);

        $mail->setFrom($company->email, $company->name . ' Support');
        $mail->addAddress($customer->email);
        $mail->isHTML(true);
        $paramArray = [
            'passageName' => $customer->first_name . " " . $customer->last_name,
            'sendTime' => date("D M j G:i:s e Y"),
            'companyLogoUrl' => $logoUrl,
            'pickUpAddress' => $this->booking->d_address,
            'startTime' => date("D M j G:i:s e Y", strtotime($this->booking->appointed_at)),
            'spendTime' => round($this->booking->estimate_time / 60, 2),
            'totalCost' => $this->booking->total_cost,
            'confirmUrl' => $requestUrl,
            "ccy"=>$company->ccy
        ];
        app('translator')->setLocale($customer->lang);
        $view = view('custom_quote_determine', $paramArray);

        $mail->Subject = Lang::get("booking.customDetermineTitle");

        $mail->msgHTML($view);

        try {
            if (!$mail->send()) {
                RunningError::recordRunningError(
                    RunningError::STATE_FAULT,
                    RunningError::TYPE_EMAIL,
                    "booking " . $this->booking->id . " send determine email to " .
                    $customer->frist_name . " " . $customer->last_name . " fault , error info :" . $mail->ErrorInfo
                );
            } else {
                RunningError::recordRunningError(
                    RunningError::STATE_SUCCESS,
                    RunningError::TYPE_EMAIL,
                    "booking " . $this->booking->id . " send determine email to " .
                    $customer->frist_name . " " . $customer->last_name . " success "
                );
            }
        } catch (\Exception $ex) {
            RunningError::recordRunningError(
                RunningError::STATE_FAULT,
                RunningError::TYPE_EMAIL,
                "booking " . $this->booking->id . " send determine email to " .
                $customer->frist_name . " " . $customer->last_name . " fault , error info :" . $ex->getMessage()
            );
            echo $ex->getMessage();
        }
    }
}
