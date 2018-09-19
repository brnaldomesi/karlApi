<?php

namespace App\Console\Commands;

use App\Model\Bill;
use App\Model\Company;
use App\Model\RunningError;
use App\Model\User;
use Faker\Provider\cs_CZ\DateTime;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SendingSettleEmail extends Command
{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'email:settle';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'send settle email';

    /**
     * Create a new command instance.
     *
     */
    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $sendTime = $_SERVER['SETTLE_TIME'];
        $receiveTimezone = $_SERVER['TIME_ZONE'];
        $nowTimestamp = time();
        $nowTimestamp = $nowTimestamp - $nowTimestamp % 100;  //取整
        $endTimestamp = $nowTimestamp - $sendTime * 3600;  //结束时间
        $startTimestamp = $endTimestamp - 24 * 3600;  //开始时间


        $bills = Company::select(
        //平台应收费用，为正收取limo公司费用，为负付给limo公司费用
            DB::raw("ifnull(bookings.platform_income, 0) - ifnull(an_bookings.an_fee, 0) as pay "),
            //支付给平台的费用
            DB::raw("ifnull(bookings.pay_plate, 0) AS pay_plate"),
            //支付给其他公司的费用
            DB::raw("ifnull(bookings.an_fee, 0) AS pay_other"),
            //平台提取费用（pay_plate+pay_other）
            DB::raw("ifnull(bookings.platform_income, 0) AS plate"),
            //limo公司赚取的an费用
            DB::raw("ifnull(an_bookings.an_fee, 0) AS an_fee"),
            "companies.name")
            ->leftjoin(DB::raw("   
                      (SELECT
              sum(ifnull(bills.platform_income,0)) as pay_plate,
              sum(ifnull(bills.an_fee,0)) as an_fee,
               sum(ifnull(bills.platform_income, 0) + ifnull(bills.an_fee, 0)) AS platform_income,
               bookings.company_id
             FROM `bookings`
               LEFT JOIN `bills` ON `bookings`.`id` = `bills`.`booking_id`
                      WHERE unix_timestamp(bills.settle_time) BETWEEN {$startTimestamp} AND {$endTimestamp}
             GROUP BY bookings.company_id) AS bookings"), "bookings.company_id", "=", "companies.id")
            ->leftjoin(DB::raw("(SELECT
               sum(ifnull(bills.an_fee,0)) AS an_fee,
               bookings.exe_com_id
             FROM `bookings`
               LEFT JOIN `bills` ON `bookings`.`id` = `bills`.`booking_id`
                WHERE unix_timestamp(bills.settle_time) BETWEEN {$startTimestamp} AND {$endTimestamp}
             GROUP BY bookings.exe_com_id) AS an_bookings"), "companies.id", "=", "an_bookings.exe_com_id")
            ->orderBy("companies.id", "ASC")
            ->get();

        $host = $_SERVER['SMTP_HOST'];
        $email = $_SERVER['SMTP_EMAIL'];
        $pwd = $_SERVER['SMTP_PWD'];
        $port = $_SERVER['SMTP_PORT'];
        $mail = $this->initPhpEmailBox($host, $email, $pwd, $port);

        $mail->setFrom($email, 'KARL Support');
        $mail->isHTML(true);
        $startTime = new \DateTime("@{$startTimestamp}");
        $endTime = new \DateTime("@{$endTimestamp}");
        $timezone = new \DateTimeZone($receiveTimezone);
        $startTime->setTimezone($timezone);
        $endTime->setTimezone($timezone);

        //TODO superadmin config
        $superadmin = User::where('company_id', 0)->first();
        $view = view('super_admin_settle_email', ['settleDatas' => $bills, "startTime" => $startTime, "endTime" => $endTime]);
        $mail->Subject = "Yesterday settle Bill";
        $mail->addAddress($superadmin->email);//$customer->email
        $mail->msgHTML($view);

        try {
            if (!$mail->send()) {
                RunningError::recordRunningError(
                    RunningError::TYPE_EMAIL,
                    RunningError::STATE_FAULT,
                    "send settle email fault , error info :" . $mail->ErrorInfo
                );
            } else {
                RunningError::recordRunningError(
                    RunningError::TYPE_EMAIL,
                    RunningError::STATE_SUCCESS,
                    "send settle email success "
                );
            }
        } catch (\Exception $ex) {
            RunningError::recordRunningError(
                RunningError::TYPE_EMAIL,
                RunningError::STATE_FAULT,
                "send settle email fault , error info :" . $ex->getMessage()
            );
        }
    }

    protected function initPhpEmailBox($host, $username, $pwd, $port, $needDebug = false)
    {
        $mail = new \PHPMailer();
        if ($needDebug) {
            $mail->CharSet = "utf-8";
            $mail->SMTPDebug = 2;
            $mail->Debugoutput = 'html';
        }
        $mail->isSMTP();
        $mail->Host = $host;
        $mail->SMTPAuth = true;
        $mail->Username = $username;
        $mail->Password = $pwd;
        $mail->SMTPSecure = 'tls';
        $mail->Port = $port;

        return $mail;
    }
}
