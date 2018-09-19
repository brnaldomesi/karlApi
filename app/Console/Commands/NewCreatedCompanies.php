<?php

namespace App\Console\Commands;

use App\Model\Admin;
use Illuminate\Support\Facades\DB;
use Illuminate\Console\Command;

class NewCreatedCompanies extends Command
{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'new:companies';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'send password email';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $admins = Admin::leftjoin("users","users.id","=","admins.user_id")
            ->leftjoin("companies","companies.id","=","users.company_id")
            ->whereNotNull("companies.id")
            ->select(
                DB::raw("concat(users.first_name,' ',users.last_name) as adminName"),
                "companies.name",
                "users.mobile as adminMobile",
                "users.email as adminEmail",
                "companies.name as comName",
                "companies.email as comEmail",
                "companies.phone1 as comPhone1",
                "companies.phone2 as comPhone2"
            )->orderBy("companies.created_at","desc")
            ->get();
        $view = view("new_companies",["admins"=>$admins]);
        $mail = new \PHPMailer();
        $mail->isSMTP();
        $mail->Timeout=30;
        $mail->Host = $_SERVER["SMTP_HOST"];
        $mail->SMTPAuth = true;
        $mail->Username = $_SERVER["SMTP_EMAIL"];
        $mail->Password = $_SERVER["SMTP_PWD"];
        $mail->SMTPSecure = 'tls';
        $mail->Port = $_SERVER["SMTP_PORT"];
        $mail->setFrom($_SERVER['SMTP_EMAIL'],"support");
        $mail->addAddress("mike@karl.limo");
        $mail->addAddress("clement@karl.limo");
        $mail->addAddress("liqihai1987@gmail.com");
        $mail->isHTML(true);
        $mail->msgHTML($view);
        if(!$mail->send()){
            \Log::info("error is ".$mail->ErrorInfo);
        }
    }
}

