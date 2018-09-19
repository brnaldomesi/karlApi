<?php
use Illuminate\Support\Facades\DB;

/**
 * Created by PhpStorm.
 * User: liqihai
 * Date: 16/9/19
 * Time: 下午3:31
 */

class UpdateCompanyEmailPassword extends BaseSeeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    protected function task()
    {
        DB::transaction(function(){
            $companies = \App\Model\Company::all();
            \Illuminate\Support\Facades\Log::info($companies);
            foreach ($companies as $company) {
                $company->email_password = base64_encode($company->email_password);
                $company->save();
            }
        });
    }
}
