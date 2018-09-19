<?php

namespace App\Jobs;

use App\Method\GeoLocationAlgorithm;
use App\Method\MethodAlgorithm;
use App\Method\UrlSpell;
use App\Model\Booking;
use App\Model\Company;
use App\Model\Offer;
use App\Model\RunningError;
use App\QueueName;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Log;

class SendEmailCustomerInvoiceJob extends Job
{
    /**
     * Create a new job instance.
     *
     * @return void
     */
    protected $booking_id;
    protected $ccAddresses;
    protected $email;

    /**
     * SendEmailCustomerInvoiceJob constructor.
     * @param $booking_id
     * @param null $ccAddresses array
     * @param null $email string
     */
    public function __construct($booking_id,$ccAddresses=null,$email=null)
    {
        $this->booking_id = $booking_id;
        $this->ccAddresses = $ccAddresses;
        $this->email = $email;

        $this->onQueue(QueueName::EmailCustomerInvoice);

    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function work()
    {

        $trip = Booking::getBookingForInvoice($this->booking_id);
        $trip->show_type=0;
        $customer = json_decode($trip->customer_data);
        if(strtolower($trip->lang) == 'fr'){
            $trip->android_app = $_SERVER['local_url']."/imgs/common/google_fr_app";
            $trip->ios_app = $_SERVER['local_url']."/imgs/common/ios_fr_app";
        }else{
            $trip->android_app = $_SERVER['local_url']."/imgs/common/google_app";
            $trip->ios_app = $_SERVER['local_url']."/imgs/common/ios_app";
        }
        $view = view('customer_invoice_email', ['trip' => $trip, "lang"=>$trip->lang])->render();
        $host = $trip->company_email_host;
        $email = $trip->company_email;
        $pwd = base64_decode($trip->company_email_pwd);
        $port = $trip->company_email_port;

        if (empty($host) || empty($email) || empty($pwd) || empty($port))
        {
            $host = $_SERVER['SMTP_HOST'];
            $email = $_SERVER['SMTP_EMAIL'];
            $pwd = $_SERVER['SMTP_PWD'];
            $port = $_SERVER['SMTP_PORT'];
        }

        $mail = $this->initPhpEmailBox($host, $email, $pwd, $port);

        try {
            $mail->setFrom($email, $trip->company_name . ' Support');
            $mail->Subject = Lang::get("invoice.title");
            if(!is_null($this->email)){
                $mail->addAddress($this->email);
            }else{
                $mail->addAddress($customer->email);
            }
            $mail->isHTML(true);
            $mail->msgHTML($view);
            if(!is_null($this->ccAddresses)){
                foreach ($this->ccAddresses as $ccAddress) {
                    $mail->addCC($ccAddress,$ccAddress);
                }
            }

            if (!$mail->send()) {
                Log::error($mail->ErrorInfo);
            } else {
                RunningError::recordRunningError(
                    RunningError::TYPE_EMAIL,
                    RunningError::STATE_SUCCESS,
                    "invoice send determine email to " .
                    $trip->customer_name . " success "
                );
            }
        } catch (\Exception $ex) {
            Log::error($ex);
        }
    }
}
